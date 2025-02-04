<?php
namespace NetpeakLogger\Render;
use NetpeakLogger\Render\AdminRenderer;
use NetpeakLogger\Admin;


class RenderFilters {
    /**
     * Render filter dropdown
     *
     * @param string $name Filter name
     * @param string $default_label Default label
     * @param array $options Filter options
     * @param callable $callback Callback function to format option label
     */
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
     * Get records from specified table with filters and ordering.
     *
     * @param string $table_name Table name
     * @param array $filters Filter array, key is column name, value is filter value
     * @param string $order_by Column name to order by
     * @param string $order_dir Sorting direction, ASC or DESC
     *
     * @return array Array of records
     */
    public static function get_filters($table_name, $filters = [], $order_by = 'id', $order_dir = 'DESC', $limit = 10,  $offset = 0) {
        global $wpdb;
    
        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name))) {
            return [];
        }
    
        $where_clauses = [];
        $params = [];
    
        if (!isset($filters['is_archive']) || $filters['is_archive'] === '') {
            $filters['is_archive'] = '0';
        }
    

        foreach ($filters as $column => $value) {
            if ($column === 'is_archive') {
                $where_clauses[] = "is_archive = %d";
                $params[] = intval($value);
            } elseif (!empty($value)) {
                $where_clauses[] = "{$column} = %s";
                $params[] = sanitize_text_field($value);
            }
        }
    
        $where_sql = empty($where_clauses) ? '1=1' : implode(' AND ', $where_clauses);
        $query = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$order_by} {$order_dir} LIMIT %d OFFSET %d";

        $params [] = $limit;
        $params[] = $offset;
    
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
    
        return $wpdb->get_results($query);
    }
    
    /**
     * Renders a filter form for querying database records.
     *
     * This method generates an HTML form that includes hidden fields and
     * dropdowns for filtering records from the specified database table.
     * Each filter dropdown is configured with a label, options, and an
     * optional callback function to format the option labels. If the options
     * are not provided, the method can execute a custom query to fetch them.
     *
     * @param string $table_name The name of the database table to query.
     * @param array $filters_config An associative array containing filter configurations.
     *                              Each configuration includes a 'label', 'options', and
     *                              an optional 'callback' function. If 'options' is empty
     *                              and 'query' is provided, the query will be executed
     *                              to fetch options.
     * @param array $hidden_fields An associative array of hidden fields to include in the form.
     *                             The key is the field name and the value is its value.
     */

    public static function render_filters($table_name, $filters_config, $hidden_fields = []) {
        global $wpdb;
    
        echo '<form method="GET" action="">';
    
        foreach ($hidden_fields as $name => $value) {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        }
    
        foreach ($filters_config as $filter_name => $config) {
            $label = $config['label'] ?? 'Filter';
            $options = $config['options'] ?? [];
            $callback = $config['callback'] ?? null;
    
            if (empty($options) && !empty($config['query'])) {
                $options = $wpdb->get_col($config['query']);
            }
    
            self::render_filter($filter_name, $label, $options, $callback);
        }
    
        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';

        AdminRenderer::bulk_edit_actions();
    }

}
