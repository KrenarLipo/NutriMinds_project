<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$application_ids = get_posts([
    'post_type' => 'db_application',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

foreach ($application_ids as $application_id) {
    $attachment_ids = get_post_meta($application_id, '_db_application_document_attachment_ids', true);
    if (is_array($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
            wp_delete_attachment((int) $attachment_id, true);
        }
    }

    wp_delete_post($application_id, true);
}

delete_option('db_platform_settings');
delete_option('db_privacy_policy_url');

$administrator_role = get_role('administrator');
if ($administrator_role) {
    $administrator_role->remove_cap('db_manage_applications');
}

global $wpdb;

$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
    'db_applications_per_page'
));

$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('_transient_db_') . '%',
    $wpdb->esc_like('_transient_timeout_db_') . '%'
));
