<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Admin;


class RenderFilters {
    //Filters
    public static function render_filter($name, $default_label, $options, $callback = null)
    {
        $selected = sanitize_text_field($_GET[$name] ?? '');
        echo '<select name="' . esc_attr($name) . '">';
        echo '<option value="">' . esc_html($default_label) . '</option>';

        foreach ($options as $option) {
            $is_selected = $selected === $option ? 'selected' : '';
            $label = $callback ? $callback($option) : $option;
            echo '<option value="' . esc_attr($option) . '" ' . esc_attr($is_selected) . '>' . esc_html($label) . '</option>';
        }

        echo '</select>';
    }

    /**
     * Get filtered logs from database
     *
     * @param string $table_name Database table name
     *
     * @return array
     */
    public static function get_filtered($table_name) {
        global $wpdb;
    

        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
            return [];
        }

        $filters = [
            'user_login' => sanitize_text_field($_GET['user'] ?? ''),
            'action' => sanitize_text_field($_GET['action'] ?? ''),
            'log_type' => sanitize_text_field($_GET['log_type'] ?? ''),
        ];
    
        $where_clauses = [];
        $params = [];
    

        if (!empty($filters['user_login'])) {
            $where_clauses[] = "user_login = %s";
            $params[] = $filters['user_login'];
        }
        if (!empty($filters['action'])) {
            $where_clauses[] = "action = %s";
            $params[] = $filters['action'];
        }
        if (!empty($filters['log_type'])) {
            $where_clauses[] = "log_type = %s";
            $params[] = $filters['log_type'];
        }
    

        $where_sql = empty($where_clauses) ? '1=1' : implode(' AND ', $where_clauses);
    

        $query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY date DESC";
    
        if (!empty($params)) {
            $query = $wpdb->prepare($query, ...$params);
        }
        return $wpdb->get_results($query);
    }
    

    public static function render_filters($table_name)
    {
        global $wpdb;

        echo '<form method="GET" action="">';
        echo '<input type="hidden" name="page" value="netpeak-logs">';
        echo '<input type="hidden" name="tab" value="logs">';


        self::render_filter(
            'user',
            'All Users',
            $wpdb->get_col("SELECT DISTINCT user_login FROM {$table_name}")
        );
        self::render_filter(
            'action',
            'All Actions',
            $wpdb->get_col("SELECT DISTINCT action FROM {$table_name}"),
            [Admin::class, 'format_action']
        );
        self::render_filter(
            'log_type',
            'All Log Types',
            $wpdb->get_col("SELECT DISTINCT log_type FROM {$table_name}"),
            'ucfirst'
        );

        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';
    }
}
