<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1>User Orders by Role</h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=user_orders_by_role')); ?>" class="page-title-action">Settings</a>

    <form method="get">
        <input type="hidden" name="page" value="user-orders-by-role" />
        <select name="role">
            <?php foreach ($roles as $role_key => $role_name) : ?>
                <option value="<?php echo esc_attr($role_key); ?>" <?php selected($selected_role, $role_key); ?>>
                    <?php echo esc_html($role_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php submit_button('Filter', 'secondary', 'submit', false); ?>
    </form>

    <?php if ($selected_role) : ?>
        <form method="post" class="export-form">
            <?php wp_nonce_field('export_csv_action', 'export_csv_nonce'); ?>
            <input type="hidden" name="export_csv" value="1">
            <input type="hidden" name="paged" value="<?php echo $paged; ?>">
            <input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">
            <?php submit_button('Export CSV', 'secondary', 'submit', false); ?>
        </form>

        <table class="wp-list-table widefat fixed striped users" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Last Order Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) :
                    $user_id = $user->ID;
                    $last_order_date = $this->get_last_order_date($user_id);
                    $row_style = '';
                    if (
                        $last_order_date === 'No orders' ||
                        (strtotime($last_order_date) < strtotime("-$months_difference months"))
                    ) {
                        $row_style = ' style="background-color: #ffcccc;"';
                    }
                ?>
                    <tr<?php echo $row_style; ?>>
                        <td><?php echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')'; ?></td>
                        <td><?php echo esc_html($last_order_date); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array(
                                            'change_user_role' => '1',
                                            'user_id' => $user_id,
                                            'role' => $selected_role,
                                            '_wpnonce' => wp_create_nonce('change_user_role')
                                        ))); ?>">
                                Change to Customer
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
</div>