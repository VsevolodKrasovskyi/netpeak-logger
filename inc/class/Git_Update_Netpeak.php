<?php
namespace NetpeakLogger;
use NetpeakLogger\Parsedown;
// Prevent loading this file directly and/or if the class is already defined
if ( ! defined( 'ABSPATH' ) || class_exists( 'Git_Update_Netpeak' ) || class_exists( 'Git_Update_Netpeak' ) )
	return;
class Git_Update_Netpeak {
	
	/**
	 * GitHub Updater version
	 */
	const VERSION = 1.6;

	/**
	 * @var $config the config for the updater
	 * @access public
	 */
	var $config;

	/**
	 * @var $missing_config any config that is missing from the initialization of this instance
	 * @access public
	 */
	var $missing_config;

	/**
	 * @var $github_data temporiraly store the data fetched from GitHub, allows us to only load the data once per class instance
	 * @access private
	 */
	private $github_data;


	/**
	 * Class Constructor
	 *
	 * @since 1.0
	 * @param array $config the configuration required for the updater to work
	 * @see has_minimum_config()
	 * @return void
	 */
	public function __construct( $config = array() ) {

		$defaults = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
			'sslverify' => true,
			'access_token' => '',
		);

		$this->config = wp_parse_args( $config, $defaults );

		// if the minimum config isn't set, issue a warning and bail
		if ( ! $this->has_minimum_config() ) {
			$message = 'The GitHub Updater was initialized without the minimum required configuration, please check the config in your plugin. The following params are missing: ';
			$message .= implode( ',', $this->missing_config );
			_doing_it_wrong( __CLASS__, $message , self::VERSION );
			return;
		}		

		$this->set_defaults();

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
		add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ) );
		add_filter( 'http_request_args', array( $this, 'http_request_sslverify' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'add_check_update_button' ), 10, 2 );
		add_action( 'wp_ajax_check_plugin_update_' . $this->config['proper_folder_name'], array( $this, 'ajax_check_update' ) );

		add_action( 'admin_footer', function() {
			?>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const updateButton = document.querySelector('.netpeak-logger-update');
					if ( updateButton ) {
						updateButton.addEventListener('click', e=> {
							e.preventDefault();
							updateButton.textContent = 'Checking...';

							const pluginSlug = updateButton.getAttribute('data-plugin-slug');
							
							fetch(ajaxurl, {
								method: 'POST',
								headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
								body: new URLSearchParams({ action: 'check_plugin_update_' + pluginSlug })
							})
							.then(response => response.json())
							.then(data => {
								if (data.success) {
									updateButton.textContent = data.data.update_available 
										? `New version available: ${data.data.new_version}` 
										: `No updates (v${data.data.current_version})`;
								} else {
									updateButton.textContent = 'Error checking update!';
								}
							})
							.catch(() => updateButton.textContent = 'Update check failed!');
						})
					}
				})
			</script>
			<?php
		});
		
	}

	public function has_minimum_config() {

		$this->missing_config = array();

		$required_config_params = array(
			'api_url',
			'raw_url',
			'github_url',
			'zip_url',
			'requires',
			'tested',
			'readme',
		);

		foreach ( $required_config_params as $required_param ) {
			if ( empty( $this->config[$required_param] ) )
				$this->missing_config[] = $required_param;
		}

		return ( empty( $this->missing_config ) );
	}


	/**
	 * Check wether or not the transients need to be overruled and API needs to be called for every single page load
	 *
	 * @return bool overrule or not
	 */
	public function overrule_transients() {
		return ( defined( 'WP_GITHUB_FORCE_UPDATE' ) && WP_GITHUB_FORCE_UPDATE );
	}


	/**
	 * Set defaults
	 *
	 * @since 1.2
	 * @return void
	 */
	public function set_defaults() {
		if ( !empty( $this->config['access_token'] ) ) {

			extract( parse_url( $this->config['zip_url'] ) ); 

			$zip_url = $scheme . '://api.github.com/repos' . $path;
			$zip_url = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $zip_url );

			$this->config['zip_url'] = $zip_url;
		}
		if ( ! isset( $this->config['new_version'] ) )
			$this->config['new_version'] = $this->get_new_version();

		if ( ! isset( $this->config['last_updated'] ) )
			$this->config['last_updated'] = $this->get_date();

		if ( ! isset( $this->config['description'] ) )
			$this->config['description'] = $this->get_description();

		$plugin_data = $this->get_plugin_data();
		if ( ! isset( $this->config['plugin_name'] ) )
			$this->config['plugin_name'] = $plugin_data['Name'];

		if ( ! isset( $this->config['version'] ) )
			$this->config['version'] = $plugin_data['Version'];

		if ( ! isset( $this->config['author'] ) )
			$this->config['author'] = $plugin_data['Author'];

		if ( ! isset( $this->config['homepage'] ) )
			$this->config['homepage'] = $plugin_data['PluginURI'];

		if ( ! isset( $this->config['readme'] ) )
			$this->config['readme'] = 'README.md';

	}


	/**
	 * Callback fn for the http_request_timeout filter
	 *
	 * @since 1.0
	 * @return int timeout value
	 */
	public function http_request_timeout() {
		return 2;
	}

	/**
	 * Callback fn for the http_request_args filter
	 *
	 * @param unknown $args
	 * @param unknown $url
	 *
	 * @return mixed
	 */
	public function http_request_sslverify( $args, $url ) {
		if ( $this->config[ 'zip_url' ] == $url )
			$args[ 'sslverify' ] = $this->config[ 'sslverify' ];

		return $args;
	}


	/**
	 * Get New Version from GitHub
	 *
	 * @since 1.0
	 * @return int $version the version number
	 */
	public function get_new_version() {
		$version = get_site_transient( md5($this->config['slug']).'_new_version' );

		if ( $this->overrule_transients() || ( !isset( $version ) || !$version || '' == $version ) ) {

			$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . basename( $this->config['slug'] ) );

			if ( is_wp_error( $raw_response ) )
				$version = false;

			if (is_array($raw_response)) {
				if (!empty($raw_response['body']))
					preg_match( '/.*Version\:\s*(.*)$/mi', $raw_response['body'], $matches );
			}

			if ( empty( $matches[1] ) )
				$version = false;
			else
				$version = $matches[1];

			// back compat for older readme version handling
			// only done when there is no version found in file name
			if ( false === $version ) {
				$raw_response = $this->remote_get( trailingslashit( $this->config['raw_url'] ) . $this->config['readme'] );

				if ( is_wp_error( $raw_response ) )
					return $version;

				preg_match( '#^\s*`*~Current Version\:\s*([^~]*)~#im', $raw_response['body'], $__version );

				if ( isset( $__version[1] ) ) {
					$version_readme = $__version[1];
					if ( -1 == version_compare( $version, $version_readme ) )
						$version = $version_readme;
				}
			}

			// refresh every 6 hours
			if ( false !== $version )
				set_site_transient( md5($this->config['slug']).'_new_version', $version, 60*60*6 );
		}

		return $version;
	}


	/**
	 * Interact with GitHub
	 *
	 * @param string $query
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function remote_get( $query ) {
		if ( ! empty( $this->config['access_token'] ) )
			$query = add_query_arg( array( 'access_token' => $this->config['access_token'] ), $query );

		$raw_response = wp_remote_get( $query, array(
			'sslverify' => $this->config['sslverify']
		) );

		return $raw_response;
	}


	/**
	 * Get GitHub Data from the specified repository
	 *
	 * @since 1.0
	 * @return array $github_data the data
	 */
	public function get_github_data() {
		if ( isset( $this->github_data ) && ! empty( $this->github_data ) ) {
			$github_data = $this->github_data;
		} else {
			$github_data = get_site_transient( md5($this->config['slug']).'_github_data' );

			if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
				$github_data = $this->remote_get( $this->config['api_url'] );

				if ( is_wp_error( $github_data ) )
					return false;

				$github_data = json_decode( $github_data['body'] );

				// refresh every 6 hours
				set_site_transient( md5($this->config['slug']).'_github_data', $github_data, 60*60*6 );
			}

			// Store the data in this class instance for future calls
			$this->github_data = $github_data;
		}

		return $github_data;
	}


	/**
	 * Get update date
	 *
	 * @since 1.0
	 * @return string $date the date
	 */
	public function get_date() {
		$_date = $this->get_github_data();
		return ( !empty( $_date->updated_at ) ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
	}


	/**
	 * Get plugin description
	 *
	 * @since 1.0
	 * @return string $description the description
	 */
	public function get_description() {
		$_description = $this->get_github_data();
		return ( !empty( $_description->description ) ) ? $_description->description : false;
	}

	
	/**
	 * Get Plugin data
	 *
	 * @since 1.0
	 * @return object $data the data
	 */
	public function get_plugin_data() {
		include_once ABSPATH.'/wp-admin/includes/plugin.php';
		$data = get_plugin_data( WP_PLUGIN_DIR.'/'.$this->config['slug'] );
		return $data;
	}


	/**
	 * Hook into the plugin update check and connect to GitHub
	 *
	 * @since 1.0
	 * @param object  $transient the plugin data transient
	 * @return object $transient updated plugin data transient
	 */
	public function api_check( $transient ) {
		if (empty($transient->checked)) {
			return $transient; 
		}
		$new_version = $this->get_new_version();
		$current_version = $this->config['version'];
	
		if (version_compare($new_version, $current_version, '>')) {
			$response = new \stdClass();
			$response->new_version = $new_version;
			$response->slug = $this->config['slug'];
			$response->plugin = $this->config['slug'];
			$response->url = $this->config['github_url'];
			$response->package = $this->config['zip_url'];
			$transient->response[$this->config['slug']] = $response;
		}

		return $transient;
	}


	/**
	 * Get changelog from GitHub
	 *
	 * @since 1.0
	 * @return string $changelog the changelog
	 */
	private function get_changelog() {
		$changelog_url = trailingslashit($this->config['raw_url']) . 'changelog/changelog.md';
		
	
		// Execute the enquiry на GitHub
		$response = wp_remote_get($changelog_url, array(
			'sslverify' => $this->config['sslverify']
		));
	
		if (is_wp_error($response)) {
			return 'Unable to fetch changelog: ' . $response->get_error_message();
		}
	
		$body = wp_remote_retrieve_body($response);

		// If the body is empty, return an error message
		if (empty($body)) {
			return 'Changelog is empty or not found.';
		}
		$Parsedown = new Parsedown();
    	return $Parsedown->text($body); 
	}

	/**
	 * Format an array of screenshots into a HTML string
	 *
	 * @param array $screenshots An array of screenshot URLs
	 * @return string A HTML string of the formatted screenshots
	 */
	private function get_screenshots(array $screenshots): string {
		if (empty($screenshots)) {
			return 'No screenshots available.';
		}
	
		$formatted = '<div class="screenshot-gallery" style="display: flex; flex-wrap: wrap; gap: 10px;">';
		foreach ($screenshots as $url) {
			$formatted .= '<a href="' . esc_url($url) . '" target="_blank" onclick="return openScreenshot(event, \'' . esc_url($url) . '\')">';
			$formatted .= '<img class="screenshot-changelog" src="' . esc_url($url) . '" alt="Screenshot">';
			$formatted .= '</a>';
		}
		$formatted .= '</div>';
	
		return $formatted;
	}
	
	/**
	 * Get Plugin info
	 *
	 * @since 1.0
	 * @param bool    $false  always false
	 * @param string  $action the API function being performed
	 * @param object  $args   plugin arguments
	 * @return object $response the plugin info
	 */
	public function get_plugin_info( $false, $action, $response ) {

		// if ( !isset( $response->slug ) || dirname( $this->config['slug'] ) != $response->slug ) {
		// 	return false;
		// }
		$response = new \stdClass();
		$response->slug = $this->config['slug'];
		$response->name = $this->config['plugin_name'];
		$response->version = $this->config['new_version'];
		$response->author = $this->config['author'];
		$response->homepage = $this->config['homepage'];
		$response->requires = $this->config['requires'];
		$response->tested = $this->config['tested'];
		$response->last_updated = $this->config['last_updated'];
		$response->sections = array( 
			'description' => $this->config['description'],
			'changelog'   => $this->get_changelog(),
			'screenshots' =>$this->get_screenshots($this->config['screenshots']),
		);
		$response->download_link = $this->config['zip_url'];
		$response->banners = array(
			'low'  => $this->config['banner'],
		);

		return $response;
	}

	/**
	 * Add a button to the plugin row that allows user to check for an update
	 *
	 * @since 1.0
	 * @param array  $links the links that will be displayed in the plugin row
	 * @param string $file  the plugin file
	 * @return array the modified links
	 */
	public function add_check_update_button( $links, $file ) {
		if ( $file === $this->config['slug'] ) {
			$links[] = '<a href="#" class="netpeak-logger-update" data-plugin="' . esc_attr( $file ) . '" data-plugin-slug="' . esc_attr( $this->config['proper_folder_name'] ) . '">Check Update</a>';
		}
		return $links;
	}


	public function ajax_check_update() {
		if (!current_user_can('update_plugins')) {
			wp_send_json_error([
				'message' => 'You do not have permission to perform this action.'
			]);
			return;
		}
	
		delete_site_transient(md5($this->config['slug']).'_new_version');
		delete_site_transient(md5($this->config['slug']).'_github_data');
		delete_site_transient('update_plugins');
	
		$this->get_github_data();
		$new_version = $this->get_new_version();
		$current_version = $this->config['version'];
		$this->config['new_version'] = $new_version;
	
		$transient = get_site_transient('update_plugins');
		if (!is_object($transient)) {
			$transient = new \stdClass();
		}
	
		if (!isset($transient->response)) {
			$transient->response = [];
		}
	
		$update = version_compare($new_version, $current_version, '>');
		if ($update) {
			$response = new \stdClass();
			$response->new_version = $new_version;
			$response->slug = $this->config['slug'];
			$response->plugin = $this->config['slug'];
			$response->url = add_query_arg(['access_token' => $this->config['access_token']], $this->config['github_url']);
			$response->package = $this->config['zip_url'];
	
			$transient->response[$this->config['slug']] = $response;
	
			set_site_transient('update_plugins', $transient);
		}
	
		wp_send_json_success([
			'message' => sprintf(
				'Update check completed successfully at %s. %s',
				date('Y-m-d H:i:s'), 
				$update ? 'New version available!' : 'No new updates.'
			),
			'current_version' => $current_version,
			'new_version' => $new_version,
			'update_available' => $update
		]);
	}
	
	
	/**
	 * Upgrader/Updater
	 * Move & activate the plugin, echo the update message
	 *
	 * @since 1.0
	 * @param boolean $true       always true
	 * @param mixed   $hook_extra not used
	 * @param array   $result     the result of the move
	 * @return array $result the result of the move
	 */
	public function upgrader_post_install( $true, $hook_extra, $result ) {

		global $wp_filesystem;

		// Move & Activate
		$proper_destination = WP_PLUGIN_DIR.'/'.$this->config['proper_folder_name'];
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;
		$activate = activate_plugin( WP_PLUGIN_DIR.'/'.$this->config['slug'] );

		// Output the update message
		$fail  = __( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.', 'github_plugin_updater' );
		$success = __( 'Plugin reactivated successfully.', 'github_plugin_updater' );
		echo is_wp_error( $activate ) ? $fail : $success;
		return $result;

	}
}
