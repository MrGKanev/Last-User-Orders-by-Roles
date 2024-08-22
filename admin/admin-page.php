<?php defined('ABSPATH') || exit; ?>

<div class="wrap user-orders-by-role">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="user-orders-controls">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=user_orders_by_role')); ?>" class="page-title-action">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e('Settings', 'last-user-orders-by-roles'); ?>
        </a>

        <form method="get" class="filter-form">
            <input type="hidden" name="page" value="user-orders-by-role" />
            <select name="role" id="role-select">
                <?php foreach ($roles as $role_key => $role_name) : ?>
                    <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                        <?php echo esc_html($role_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'last-user-orders-by-roles'), 'secondary', 'submit', false); ?>
        </form>

        <?php if ($selected_role && !empty($users)) : ?>
            <form method="post" class="export-form">
                <?php wp_nonce_field('export_csv_action', 'export_csv_nonce'); ?>
                <input type="hidden" name="export_csv" value="1">
                <input type="hidden" name="paged" value="<?php echo $paged; ?>">
                <input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">
                <?php submit_button(__('Export CSV', 'last-user-orders-by-roles'), 'secondary', 'submit', false, array('id' => 'export-csv-button')); ?>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($selected_role) : ?>
        <?php if (empty($users)) : ?>
            <div class="no-users-message">
                <p><?php echo sprintf(esc_html__('There are no users in the %s role.', 'last-user-orders-by-roles'), esc_html($roles[$selected_role])); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'last-user-orders-by-roles'); ?></th>
                        <th><?php esc_html_e('Last Order Date', 'last-user-orders-by-roles'); ?></th>
                        <th><?php esc_html_e('Actions', 'last-user-orders-by-roles'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) :
                        $user_id = $user->ID;
                        $last_order_date = $this->get_last_order_date($user_id);
                        $inactive_class = (
                            $last_order_date === 'No orders' ||
                            (strtotime($last_order_date) < strtotime("-$months_difference months"))
                        ) ? ' class="inactive-user"' : '';
                    ?>
                        <tr<?php echo $inactive_class; ?>>
                            <td><?php echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')'; ?></td>
                            <td><?php echo esc_html($last_order_date); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array(
                                                'change_user_role' => '1',
                                                'user_id' => $user_id,
                                                'role' => $selected_role,
                                                '_wpnonce' => wp_create_nonce('change_user_role')
                                            ))); ?>" class="button button-secondary change-role-button">
                                    <?php esc_html_e('Change to Customer', 'last-user-orders-by-roles'); ?>
                                </a>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $paged
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>