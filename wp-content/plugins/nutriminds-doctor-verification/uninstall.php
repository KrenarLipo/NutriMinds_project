<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$application_ids = get_posts([
    'post_type' => 'nm_specialist_app',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$attachment_meta_keys = [
    '_nm_application_license_attachment_id',
    '_nm_application_credential_attachment_id',
    '_nm_application_identity_attachment_id',
];

foreach ($application_ids as $application_id) {
    foreach ($attachment_meta_keys as $meta_key) {
        $attachment_id = (int) get_post_meta($application_id, $meta_key, true);
        if ($attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }

    wp_delete_post($application_id, true);
}

delete_option('nutriminds_platform_settings');

$administrator_role = get_role('administrator');
if ($administrator_role) {
    $administrator_role->remove_cap('nutriminds_manage_applications');
}

global $wpdb;

$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
    'nutriminds_applications_per_page'
));

$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('_transient_nutriminds_') . '%',
    $wpdb->esc_like('_transient_timeout_nutriminds_') . '%'
));
