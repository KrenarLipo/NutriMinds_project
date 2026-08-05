<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

foreach (['nms_registration', 'nms_project'] as $post_type) {
    $post_ids = get_posts([
        'post_type' => $post_type,
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    foreach ($post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }
}

delete_option('nms_default_project_seeded');

$administrator_role = get_role('administrator');
if ($administrator_role) {
    $administrator_role->remove_cap('nms_manage_surveys');
}

global $wpdb;

$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('_transient_nms_') . '%',
    $wpdb->esc_like('_transient_timeout_nms_') . '%'
));
