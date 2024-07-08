<?php

/**
 * Plugin Name:       Last User Orders by Roles
 * Plugin URI:        https://github.com/MrGKanev/Bulk-Order-Editor/
 * Description:       Displays users' last order dates and allows changing roles based on order inactivity.
 * Requires at least: 5.5
 * Version:           0.0.1
 * Author:            Gabriel Kanev
 * Author URI:        https://gkanev.com
 * License:           MIT
 * License URI:       https://github.com/MrGKanev/Last-User-Orders-by-Roles/blob/master/LICENSE
 * GitHub Plugin URI: https://github.com/MrGKanev/Last-User-Orders-by-Roles
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    add_action('admin_init', 'initialize_user_orders_by_role_settings');

    function initialize_user_orders_by_role_settings()
    {
        add_filter('woocommerce_settings_tabs_array', 'add_user_orders_by_role_settings_tab', 50);
        add_action('woocommerce_settings_tabs_user_orders_by_role', 'user_orders_by_role_settings_tab_content');
        add_action('woocommerce_update_options_user_orders_by_role', 'update_user_orders_by_role_settings');
    }

    function add_user_orders_by_role_settings_tab($settings_tabs)
    {
        $settings_tabs['user_orders_by_role'] = __('User Orders by Role', 'text-domain');
        return $settings_tabs;
    }

    function user_orders_by_role_settings_tab_content()
    {
        woocommerce_admin_fields(get_user_orders_by_role_settings());
    }

    function get_user_orders_by_role_settings()
    {
        $roles_options = wp_list_pluck(get_editable_roles(), 'name');

        $settings = array(
            'section_title' => array(
                'name' => __('User Orders by Role Settings', 'text-domain'),
                'type' => 'title',
                'desc' => '',
                'id' => 'user_orders_by_role_section_title'
            ),
            'months_difference' => array(
                'name' => __('Months Difference', 'text-domain'),
                'type' => 'number',
                'desc' => __('Number of months to check for order inactivity.', 'text-domain'),
                'id' => 'user_orders_by_role_months_difference',
                'default' => '4',
                'desc_tip' => true,
            ),
            'default_role' => array(
                'name' => __('Default Role', 'text-domain'),
                'type' => 'select',
                'desc' => __('Default role to set for inactive users.', 'text-domain'),
                'id' => 'user_orders_by_role_default_role',
                'options' => $roles_options,
                'default' => 'customer',
                'desc_tip' => true,
            ),
            'users_per_page' => array(
                'name' => __('Users Per Page', 'text-domain'),
                'type' => 'number',
                'desc' => __('Number of users to display per page in the admin list.', 'text-domain'),
                'id' => 'user_orders_by_role_users_per_page',
                'default' => '25',
                'desc_tip' => true,
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'user_orders_by_role_section_end'
            )
        );
        return $settings;
    }

    function update_user_orders_by_role_settings()
    {
        woocommerce_update_options(get_user_orders_by_role_settings());
    }

    // Function to add a submenu page under WooCommerce
    function add_user_orders_by_role_menu()
    {
        add_submenu_page(
            'woocommerce',
            'User Orders by Role',
            'User Orders by Role',
            'manage_woocommerce',
            'user-orders-by-role',
            'user_orders_by_role_page_content'
        );
    }
    add_action('admin_menu', 'add_user_orders_by_role_menu');

    function get_last_order_date_for_user($user_id)
    {
        $args = array(
            'customer_id' => $user_id,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
        );

        $orders = wc_get_orders($args);
        if (!empty($orders)) {
            $order_id = $orders[0];
            $order = wc_get_order($order_id);
            return $order->get_date_created()->date('Y-m-d H:i:s');
        }
        return 'No orders';
    }

    // Function to register settings
    function register_user_orders_by_role_settings() {
        register_setting('user_orders_by_role_settings_group', 'months_difference');
        register_setting('user_orders_by_role_settings_group', 'default_role');
        register_setting('user_orders_by_role_settings_group', 'users_per_page');

        add_settings_section('user_orders_by_role_main_section', 'Main Settings', null, 'user-orders-by-role-settings');

        add_settings_field('months_difference', 'Months Difference', 'months_difference_callback', 'user-orders-by-role-settings', 'user_orders_by_role_main_section');
        add_settings_field('default_role', 'Default Role', 'default_role_callback', 'user-orders-by-role-settings', 'user_orders_by_role_main_section');
        add_settings_field('users_per_page', 'Users Per Page', 'users_per_page_callback', 'user-orders-by-role-settings', 'user_orders_by_role_main_section');
    }
    add_action('admin_init', 'register_user_orders_by_role_settings');

    // Callback function for months_difference
    function months_difference_callback() {
        $value = get_option('months_difference', 4);
        echo '<input type="number" id="months_difference" name="months_difference" value="' . esc_attr($value) . '" />';
    }

    // Callback function for default_role
    function default_role_callback() {
        $value = get_option('default_role', 'Customer');
        $roles = get_editable_roles();
        echo '<select id="default_role" name="default_role">';
        foreach ($roles as $role_key => $role) {
            echo '<option value="' . esc_attr($role_key) . '" ' . selected($value, $role_key, false) . '>' . esc_html($role['name']) . '</option>';
        }
        echo '</select>';
    }

    // Callback function for users_per_page
    function users_per_page_callback() {
        $value = get_option('users_per_page', 25);
        echo '<input type="number" id="users_per_page" name="users_per_page" value="' . esc_attr($value) . '" />';
    }

    // Settings page content
    function user_orders_by_role_settings_page_content() {
        ?>
        <div class="wrap">
            <h1>User Orders by Role Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('user_orders_by_role_settings_group');
                do_settings_sections('user-orders-by-role-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Function to handle CSV export
    function handle_csv_export($users) {
        $filename = 'user_orders_by_role_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, array('User', 'Email', 'Last Order Date'));

        foreach ($users as $user) {
            $user_id = $user->ID;
            $last_order_date = get_last_order_date_for_user($user_id);
            fputcsv($output, array($user->display_name, $user->user_email, $last_order_date));
        }
        fclose($output);
        exit;
    }

    // Function to change user role to a normal WooCommerce customer
    function change_user_role_to_customer() {
        if (isset($_GET['change_user_role']) && isset($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $default_role = get_option('default_role', 'customer');
            $user = new WP_User($user_id);
            $user->set_role($default_role);
            wp_redirect(admin_url('admin.php?page=user-orders-by-role&role=' . $_GET['role']));
            exit;
        }
    }
    add_action('admin_init', 'change_user_role_to_customer');

    // Content for the User Orders by Role page
    function user_orders_by_role_page_content() {
        // Check for CSV export request
        if (isset($_POST['export_csv']) && isset($_GET['role'])) {
            $selected_role = sanitize_text_field($_GET['role']);
            $users = get_users(array('role' => $selected_role));
            handle_csv_export($users); // Handle CSV export if requested
        }

        $roles = get_editable_roles();
        $selected_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : get_option('default_role', 'customer');
        $months_difference = get_option('months_difference', 4);
        $users_per_page = get_option('users_per_page', 25);
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $offset = ($paged - 1) * $users_per_page;

        echo '<div class="wrap">';
        echo '<h1>User Orders by Role</h1>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=user_orders_by_role')) . '" class="page-title-action">Settings</a>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="user-orders-by-role" />';
        echo '<select name="role">';
        foreach ($roles as $role_key => $role) {
            echo '<option value="' . esc_attr($role_key) . '" ' . selected($selected_role, $role_key, false) . '>' . esc_html($role['name']) . '</option>';
        }
        echo '</select>';
        submit_button('Filter');
        echo '</form>';

        if ($selected_role) {
            $users = get_users(array(
                'role' => $selected_role,
                'number' => $users_per_page,
                'offset' => $offset,
            ));
            $total_users = count_users()['avail_roles'][$selected_role];
            $total_pages = ceil($total_users / $users_per_page);

            echo '<form method="post" style="margin-top: 10px;">';
            echo '<input type="hidden" name="export_csv" value="1">';
            submit_button('Export CSV', 'secondary', 'submit', false);
            echo '</form>';

            echo '<table class="wp-list-table widefat fixed striped users" style="margin-top: 20px;"><thead><tr><th>User</th><th>Last Order Date</th><th>Actions</th></tr></thead><tbody>';

            foreach ($users as $user) {
                $user_id = $user->ID;
                $last_order_date = get_last_order_date_for_user($user_id);

                // Check if the last order date is 'No orders' or more than defined months old
                $row_style = '';
                if ($last_order_date === 'No orders') {
                    $row_style = ' style="background-color: #ffcccc;"';
                } else {
                    $order_date = new DateTime($last_order_date);
                    $current_date = new DateTime();
                    $interval = $current_date->diff($order_date);
                    $months_diff = ($interval->y * 12) + $interval->m;

                    if ($months_diff >= $months_difference) {
                        $row_style = ' style="background-color: #ffcccc;"';
                    }
                }

                echo '<tr' . $row_style . '>';
                echo '<td>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</td>';
                echo '<td>' . esc_html($last_order_date) . '</td>';
                echo '<td><a href="' . esc_url(add_query_arg(array('change_user_role' => '1', 'user_id' => $user_id, 'role' => $selected_role))) . '">Change to ' . esc_html(get_option('default_role', 'customer')) . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Pagination
            echo '<div class="tablenav"><div class="tablenav-pages">';
            $current_url = esc_url(admin_url('admin.php?page=user-orders-by-role&role=' . $selected_role));
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = ($i == $paged) ? ' class="current"' : '';
                echo '<a' . $class . ' href="' . $current_url . '&paged=' . $i . '">' . $i . '</a> ';
            }
            echo '</div></div>';
        }

        echo '</div>';
    }
}