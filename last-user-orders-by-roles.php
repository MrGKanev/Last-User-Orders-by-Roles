<?php

/**
 * Plugin Name: Last User Orders by Roles
 * Plugin URI: https://github.com/MrGKanev/Bulk-Order-Editor/
 * Description: Displays users' last order dates and allows changing roles based on order inactivity.
 * Version: 0.0.3
 * Author: Gabriel Kanev
 * Author URI: https://gkanev.com
 * License: MIT
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.1.2
 */

defined('ABSPATH') || exit;

class Last_User_Orders_By_Roles
{
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        $this->initialize_settings();
    }

    public function init()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_inactive_notice'));
            return;
        }

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_user_role_change'));
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_user_orders_by_role', array($this, 'settings_tab_content'));
        add_action('woocommerce_update_options_user_orders_by_role', array($this, 'update_settings'));

        // HPOS compatibility
        add_action('before_woocommerce_init', function () {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });
    }

    public function woocommerce_inactive_notice()
    {
        echo '<div class="notice notice-error is-dismissible"><p>"Last User Orders by Roles" requires WooCommerce to be active. Please activate WooCommerce first.</p></div>';
    }

    public function add_plugin_action_links($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=user_orders_by_role') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'User Orders by Role',
            'User Orders by Role',
            'manage_woocommerce',
            'user-orders-by-role',
            array($this, 'render_admin_page')
        );
    }

    public function initialize_settings()
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_user_orders_by_role', array($this, 'settings_tab_content'));
        add_action('woocommerce_update_options_user_orders_by_role', array($this, 'update_settings'));
    }

    public function months_difference_callback()
    {
        $value = get_option('user_orders_by_role_months_difference', 4);
        echo '<input type="number" id="user_orders_by_role_months_difference" name="user_orders_by_role_months_difference" value="' . esc_attr($value) . '" />';
    }

    public function default_role_callback()
    {
        $value = get_option('user_orders_by_role_default_role', 'customer');
        $roles = wp_roles()->get_names();
        echo '<select id="user_orders_by_role_default_role" name="user_orders_by_role_default_role">';
        foreach ($roles as $role_key => $role_name) {
            echo '<option value="' . esc_attr($role_key) . '" ' . selected($value, $role_key, false) . '>' . esc_html($role_name) . '</option>';
        }
        echo '</select>';
    }

    public function users_per_page_callback()
    {
        $value = get_option('user_orders_by_role_users_per_page', 25);
        echo '<input type="number" id="user_orders_by_role_users_per_page" name="user_orders_by_role_users_per_page" value="' . esc_attr($value) . '" />';
    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['user_orders_by_role'] = __('User Orders by Role', 'last-user-orders-by-roles');
        return $settings_tabs;
    }

    public function settings_tab_content()
    {
        woocommerce_admin_fields($this->get_settings());
    }

    public function update_settings()
    {
        woocommerce_update_options($this->get_settings());
    }

    public function get_settings()
    {
        $settings = array(
            array(
                'title' => __('User Orders by Role Settings', 'last-user-orders-by-roles'),
                'type'  => 'title',
                'desc'  => '',
                'id'    => 'user_orders_by_role_section_title'
            ),
            array(
                'title'   => __('Months Difference', 'last-user-orders-by-roles'),
                'desc'    => __('Number of months to check for order inactivity.', 'last-user-orders-by-roles'),
                'id'      => 'user_orders_by_role_months_difference',
                'default' => '4',
                'type'    => 'number',
            ),
            array(
                'title'   => __('Default Role', 'last-user-orders-by-roles'),
                'desc'    => __('Default role to set for inactive users.', 'last-user-orders-by-roles'),
                'id'      => 'user_orders_by_role_default_role',
                'default' => 'customer',
                'type'    => 'select',
                'options' => wp_roles()->get_names(),
            ),
            array(
                'title'   => __('Users Per Page', 'last-user-orders-by-roles'),
                'desc'    => __('Number of users to display per page in the admin list.', 'last-user-orders-by-roles'),
                'id'      => 'user_orders_by_role_users_per_page',
                'default' => '25',
                'type'    => 'number',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'user_orders_by_role_section_end'
            )
        );
        return apply_filters('user_orders_by_role_settings', $settings);
    }

    public function render_admin_page()
    {
        $roles = wp_roles()->get_names();
        $selected_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : get_option('user_orders_by_role_default_role', 'customer');
        $months_difference = get_option('user_orders_by_role_months_difference', 4);
        $users_per_page = get_option('user_orders_by_role_users_per_page', 25);
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        if (isset($_POST['export_csv']) && check_admin_referer('export_csv_action', 'export_csv_nonce')) {
            $this->export_csv($selected_role);
        }

        $users = $this->get_users($selected_role, $users_per_page, $paged);
        $total_users = count_users()['avail_roles'][$selected_role] ?? 0;
        $total_pages = ceil($total_users / $users_per_page);

        include plugin_dir_path(__FILE__) . 'admin/admin-page.php';
    }

    private function get_users($role, $per_page, $paged)
    {
        return get_users(array(
            'role' => $role,
            'number' => $per_page,
            'paged' => $paged,
        ));
    }

    public function get_last_order_date($user_id)
    {
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
        ));

        if (!empty($orders)) {
            $order = wc_get_order($orders[0]);
            return $order->get_date_created()->date('Y-m-d H:i:s');
        }

        return 'No orders';
    }

    public function handle_user_role_change()
    {
        if (isset($_GET['change_user_role'], $_GET['user_id'], $_GET['role']) && check_admin_referer('change_user_role')) {
            $user_id = intval($_GET['user_id']);
            $user = get_userdata($user_id);
            if ($user) {
                $user->set_role('customer');
            }
            wp_safe_redirect(add_query_arg('role', $_GET['role'], admin_url('admin.php?page=user-orders-by-role')));
            exit;
        }
    }

    private function export_csv($role)
    {
        $filename = 'user_orders_by_role_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, array('User', 'Email', 'Last Order Date'));

        $users = get_users(array('role' => $role));
        foreach ($users as $user) {
            $last_order_date = $this->get_last_order_date($user->ID);
            fputcsv($output, array($user->display_name, $user->user_email, $last_order_date));
        }
        fclose($output);
        exit;
    }
}

new Last_User_Orders_By_Roles();
