<?php
/**
 * Plugin Name: NutriMinds Survey
 * Description: Public registration intake for NutriMinds research/collaboration projects (starting with the Nutrimind Gut Health Test), stored as a standing patient database.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: NutriMinds
 * Text Domain: nutriminds-survey
 */

if (!defined('ABSPATH')) {
    exit;
}

final class NutriMinds_Survey {
    private const SHORTCODE = 'nutriminds_survey';
    private const VERSION = '1.1.0';
    private const DEFAULT_LANGUAGE = 'en';
    private const LANGUAGE_COOKIE = 'nms_lang';
    private const POST_TYPE_REGISTRATION = 'nms_registration';
    private const POST_TYPE_PROJECT = 'nms_project';
    private const AJAX_ACTION = 'nms_submit_registration';
    private const NONCE_ACTION = 'nms_survey_form';
    private const META_PREFIX = '_nms_registration_';
    private const PROJECT_META_PREFIX = '_nms_project_';
    private const MANAGE_CAPABILITY = 'nms_manage_surveys';
    private const DEFAULT_PROJECT_TITLE = 'Nutrimind Gut Health Test';
    private const GENDER_OPTIONS = ['female', 'male'];
    private const ADMIN_CONTACT_EMAIL = 'klipo90@gmail.com';

    private array $translations = [];
    private ?string $current_language = null;

    public function __construct() {
        add_action('init', [$this, 'capture_language_choice']);
        add_action('init', [self::class, 'register_post_types']);
        add_shortcode(self::SHORTCODE, [$this, 'render_survey_form']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handle_registration_submission']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handle_registration_submission']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'ensure_manage_capability_granted']);
        add_action('admin_init', [self::class, 'ensure_default_project_seeded']);
        add_action('admin_post_nms_export_registrations', [$this, 'handle_export_registrations']);
        add_action('add_meta_boxes', [$this, 'register_project_meta_box']);
        add_action('save_post_' . self::POST_TYPE_PROJECT, [$this, 'save_project_meta_box'], 10, 2);
        add_filter('manage_' . self::POST_TYPE_REGISTRATION . '_posts_columns', [$this, 'filter_registration_columns']);
        add_action('manage_' . self::POST_TYPE_REGISTRATION . '_posts_custom_column', [$this, 'render_registration_column'], 10, 2);
        add_filter('manage_' . self::POST_TYPE_PROJECT . '_posts_columns', [$this, 'filter_project_columns']);
        add_action('manage_' . self::POST_TYPE_PROJECT . '_posts_custom_column', [$this, 'render_project_column'], 10, 2);
        add_action('admin_notices', [$this, 'render_export_button']);
    }

    public static function activate(): void {
        $role = get_role('administrator');
        if ($role && !$role->has_cap(self::MANAGE_CAPABILITY)) {
            $role->add_cap(self::MANAGE_CAPABILITY);
        }

        // Registered again here (idempotent) so the post type exists immediately at
        // activation time, regardless of whether 'init'/'admin_init' has fired yet —
        // WP-CLI and programmatic activation don't reliably trigger those.
        self::register_post_types();
        self::ensure_default_project_seeded();
    }

    public function ensure_manage_capability_granted(): void {
        $role = get_role('administrator');
        if ($role && !$role->has_cap(self::MANAGE_CAPABILITY)) {
            $role->add_cap(self::MANAGE_CAPABILITY);
        }
    }

    public static function ensure_default_project_seeded(): void {
        if (get_option('nms_default_project_seeded') === '1') {
            return;
        }

        $existing = get_posts([
            'post_type' => self::POST_TYPE_PROJECT,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        if (empty($existing)) {
            wp_insert_post([
                'post_type' => self::POST_TYPE_PROJECT,
                'post_status' => 'publish',
                'post_title' => self::DEFAULT_PROJECT_TITLE,
            ], true);
        }

        update_option('nms_default_project_seeded', '1', false);
    }

    public static function register_post_types(): void {
        register_post_type(self::POST_TYPE_PROJECT, [
            'labels' => [
                'name' => 'Survey Projects',
                'singular_name' => 'Survey Project',
                'add_new_item' => 'Add New Project',
                'edit_item' => 'Edit Project',
                'view_item' => 'View Project',
                'search_items' => 'Search Projects',
                'not_found' => 'No projects found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => self::MANAGE_CAPABILITY,
                'read_post' => self::MANAGE_CAPABILITY,
                'delete_post' => self::MANAGE_CAPABILITY,
                'edit_posts' => self::MANAGE_CAPABILITY,
                'edit_others_posts' => self::MANAGE_CAPABILITY,
                'publish_posts' => self::MANAGE_CAPABILITY,
                'read_private_posts' => self::MANAGE_CAPABILITY,
                'delete_posts' => self::MANAGE_CAPABILITY,
                'delete_others_posts' => self::MANAGE_CAPABILITY,
            ],
            'map_meta_cap' => true,
        ]);

        register_post_type(self::POST_TYPE_REGISTRATION, [
            'labels' => [
                'name' => 'Survey Registrations',
                'singular_name' => 'Survey Registration',
                'add_new_item' => 'Add Registration',
                'edit_item' => 'Registration Details',
                'view_item' => 'View Registration',
                'search_items' => 'Search Registrations',
                'not_found' => 'No registrations found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => self::MANAGE_CAPABILITY,
                'read_post' => self::MANAGE_CAPABILITY,
                'delete_post' => self::MANAGE_CAPABILITY,
                'edit_posts' => self::MANAGE_CAPABILITY,
                'edit_others_posts' => self::MANAGE_CAPABILITY,
                'publish_posts' => self::MANAGE_CAPABILITY,
                'read_private_posts' => self::MANAGE_CAPABILITY,
                'delete_posts' => self::MANAGE_CAPABILITY,
                'delete_others_posts' => self::MANAGE_CAPABILITY,
            ],
            'map_meta_cap' => true,
        ]);
    }

    public function register_admin_menu(): void {
        add_menu_page(
            'NutriMinds Survey',
            'NutriMinds Survey',
            self::MANAGE_CAPABILITY,
            'edit.php?post_type=' . self::POST_TYPE_REGISTRATION,
            '',
            'dashicons-forms',
            27
        );

        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE_REGISTRATION,
            'All Registrations',
            'All Registrations',
            self::MANAGE_CAPABILITY,
            'edit.php?post_type=' . self::POST_TYPE_REGISTRATION
        );

        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE_REGISTRATION,
            'Projects',
            'Projects',
            self::MANAGE_CAPABILITY,
            'edit.php?post_type=' . self::POST_TYPE_PROJECT
        );

        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE_REGISTRATION,
            'Add New Project',
            'Add New Project',
            self::MANAGE_CAPABILITY,
            'post-new.php?post_type=' . self::POST_TYPE_PROJECT
        );
    }

    public function register_project_meta_box(): void {
        add_meta_box(
            'nms_project_dates',
            'Project Dates',
            [$this, 'render_project_meta_box'],
            self::POST_TYPE_PROJECT,
            'normal',
            'high'
        );
    }

    public function render_project_meta_box(WP_Post $post): void {
        wp_nonce_field('nms_project_dates_' . $post->ID, 'nms_project_dates_nonce');
        $start_date = (string) get_post_meta($post->ID, self::PROJECT_META_PREFIX . 'start_date', true);
        $end_date = (string) get_post_meta($post->ID, self::PROJECT_META_PREFIX . 'end_date', true);

        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="nms_project_start_date">Start date</label></th><td><input type="date" id="nms_project_start_date" name="nms_project_start_date" value="' . esc_attr($start_date) . '"><p class="description">Leave blank to make the project active immediately.</p></td></tr>';
        echo '<tr><th scope="row"><label for="nms_project_end_date">End date</label></th><td><input type="date" id="nms_project_end_date" name="nms_project_end_date" value="' . esc_attr($end_date) . '"><p class="description">Leave blank to keep the project open-ended (e.g. until a participant target is reached). Once this date passes, the project stops appearing in the registration form.</p></td></tr>';
        echo '</tbody></table>';
    }

    public function save_project_meta_box(int $post_id, WP_Post $post): void {
        if (!isset($_POST['nms_project_dates_nonce']) || !wp_verify_nonce((string) $_POST['nms_project_dates_nonce'], 'nms_project_dates_' . $post_id)) {
            return;
        }

        if (!current_user_can(self::MANAGE_CAPABILITY, $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->save_optional_date_meta($post_id, self::PROJECT_META_PREFIX . 'start_date', (string) ($_POST['nms_project_start_date'] ?? ''));
        $this->save_optional_date_meta($post_id, self::PROJECT_META_PREFIX . 'end_date', (string) ($_POST['nms_project_end_date'] ?? ''));
    }

    private function save_optional_date_meta(int $post_id, string $meta_key, string $raw_value): void {
        $value = sanitize_text_field(wp_unslash($raw_value));

        if ($value === '' || !$this->is_valid_date($value)) {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        update_post_meta($post_id, $meta_key, $value);
    }

    private function is_valid_date(string $value): bool {
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function get_active_projects(): array {
        $today = current_time('Y-m-d');

        $query = new WP_Query([
            'post_type' => self::POST_TYPE_PROJECT,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    ['key' => self::PROJECT_META_PREFIX . 'start_date', 'compare' => 'NOT EXISTS'],
                    ['key' => self::PROJECT_META_PREFIX . 'start_date', 'value' => $today, 'compare' => '<=', 'type' => 'DATE'],
                ],
                [
                    'relation' => 'OR',
                    ['key' => self::PROJECT_META_PREFIX . 'end_date', 'compare' => 'NOT EXISTS'],
                    ['key' => self::PROJECT_META_PREFIX . 'end_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'],
                ],
            ],
        ]);

        $projects = [];
        foreach ($query->posts as $post) {
            $projects[] = [
                'id' => (int) $post->ID,
                'name' => get_the_title($post),
            ];
        }

        return $projects;
    }

    private function is_active_project(int $project_id): bool {
        foreach ($this->get_active_projects() as $project) {
            if ($project['id'] === $project_id) {
                return true;
            }
        }

        return false;
    }

    private function registration_exists_for(string $email, string $phone): bool {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE_REGISTRATION,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => self::META_PREFIX . 'email', 'value' => $email, 'compare' => '='],
                ['key' => self::META_PREFIX . 'phone', 'value' => $phone, 'compare' => '='],
            ],
        ]);

        return !empty($query->posts);
    }

    private function get_already_registered_message(): string {
        return strtr($this->t('ajax.alreadyRegistered'), ['{admin_email}' => self::ADMIN_CONTACT_EMAIL]);
    }

    public function capture_language_choice(): void {
        $requested_language = isset($_GET['nms_lang']) ? sanitize_key((string) $_GET['nms_lang']) : '';

        if (!$this->is_supported_language($requested_language)) {
            return;
        }

        $this->current_language = $requested_language;
        setcookie(
            self::LANGUAGE_COOKIE,
            $requested_language,
            [
                'expires' => time() + YEAR_IN_SECONDS,
                'path' => COOKIEPATH ?: '/',
                'secure' => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[self::LANGUAGE_COOKIE] = $requested_language;
    }

    private function is_supported_language(string $language): bool {
        return in_array($language, ['en', 'de'], true);
    }

    public function get_current_language(): string {
        if ($this->current_language !== null) {
            return $this->current_language;
        }

        $cookie_language = isset($_COOKIE[self::LANGUAGE_COOKIE]) ? sanitize_key((string) $_COOKIE[self::LANGUAGE_COOKIE]) : '';
        if ($this->is_supported_language($cookie_language)) {
            $this->current_language = $cookie_language;
            return $this->current_language;
        }

        $locale = determine_locale();
        $this->current_language = str_starts_with($locale, 'de') ? 'de' : self::DEFAULT_LANGUAGE;

        return $this->current_language;
    }

    private function get_translations(string $language): array {
        if (isset($this->translations[$language])) {
            return $this->translations[$language];
        }

        $path = plugin_dir_path(__FILE__) . 'languages/' . $language . '.json';
        if (!is_readable($path)) {
            $path = plugin_dir_path(__FILE__) . 'languages/' . self::DEFAULT_LANGUAGE . '.json';
        }

        $contents = is_readable($path) ? file_get_contents($path) : '{}';
        $decoded = json_decode((string) $contents, true);
        $this->translations[$language] = is_array($decoded) ? $decoded : [];

        return $this->translations[$language];
    }

    public function t(string $key): string {
        $translations = $this->get_translations($this->get_current_language());

        return $translations[$key] ?? $this->get_translations(self::DEFAULT_LANGUAGE)[$key] ?? $key;
    }

    private function t_for_language(string $key, string $language): string {
        $translations = $this->get_translations($language);

        return $translations[$key] ?? $this->get_translations(self::DEFAULT_LANGUAGE)[$key] ?? $key;
    }

    private function render_language_switcher(): string {
        $current_language = $this->get_current_language();
        $languages = [
            'en' => ['flag' => '🇬🇧', 'label' => 'English'],
            'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
        ];

        $items = '';
        foreach ($languages as $language => $meta) {
            $items .= sprintf(
                '<a class="nms-language-switcher__link %s" href="%s" aria-label="%s" title="%s"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
                $current_language === $language ? 'is-active' : '',
                esc_url(add_query_arg('nms_lang', $language)),
                esc_attr($meta['label']),
                esc_attr($meta['label']),
                esc_html($meta['flag']),
                esc_html($meta['label'])
            );
        }

        return '<div class="nms-language-switcher" aria-label="Language switcher">' . $items . '</div>';
    }

    public function register_assets(): void {
        $base_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'nutriminds-survey',
            $base_url . 'assets/css/survey.css',
            [],
            self::VERSION
        );

        wp_register_script(
            'nutriminds-survey',
            $base_url . 'assets/js/survey.js',
            [],
            self::VERSION,
            true
        );
    }

    public function render_survey_form(): string {
        wp_enqueue_script('nutriminds-survey');
        wp_localize_script('nutriminds-survey', 'NutriMindsSurvey', $this->get_client_config());

        $nutriminds_survey_config = $this->get_client_config();

        ob_start();
        require plugin_dir_path(__FILE__) . 'templates/survey-form.php';
        return (string) ob_get_clean();
    }

    private function get_client_config(): array {
        $language = $this->get_current_language();
        $translations = $this->get_translations($language);
        $keys = [
            'validation.required',
            'validation.email',
            'validation.phone',
            'validation.birthday',
            'validation.consentRequired',
            'validation.projectRequired',
            'js.submitting',
            'js.submitError',
            'button.submit',
        ];

        $picked = [];
        foreach ($keys as $key) {
            $picked[$key] = $translations[$key] ?? $key;
        }

        return [
            'language' => $language,
            'text' => $picked,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action' => self::AJAX_ACTION,
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'projects' => $this->get_active_projects(),
        ];
    }

    private function get_consent_label(): string {
        $privacy_url = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : '';
        $label = $this->t('field.consent');

        if ($privacy_url === '') {
            return $label;
        }

        return strtr($label, [
            '{privacy_policy_link}' => '<a href="' . esc_url($privacy_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($this->t('field.consentLinkText')) . '</a>',
        ]);
    }

    private function posted_text(string $key): string {
        return sanitize_text_field((string) wp_unslash($_POST[$key] ?? ''));
    }

    private function is_valid_phone(string $phone): bool {
        $normalized = preg_replace('/[^\d+]/', '', $phone) ?? '';
        $digits = preg_replace('/\D/', '', $normalized) ?? '';

        return (bool) preg_match('/^\+?[\d\s().-]{7,24}$/', $phone) && strlen($digits) >= 7 && strlen($digits) <= 20;
    }

    private function is_valid_birthday(string $birthday): bool {
        if (!$this->is_valid_date($birthday)) {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $birthday);
        $today = new DateTime(current_time('Y-m-d'));

        if ($date === false || $date > $today) {
            return false;
        }

        $age = $today->diff($date)->y;

        return $age <= 120;
    }

    private function get_rest_client_ip(): string {
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        return $remote_addr !== '' ? $remote_addr : 'unknown';
    }

    private function is_submission_rate_limited(): bool {
        $key = 'nms_submission_rate_' . md5($this->get_rest_client_ip());
        $count = (int) get_transient($key);

        if ($count >= 8) {
            return true;
        }

        set_transient($key, $count + 1, HOUR_IN_SECONDS);

        return false;
    }

    public function handle_registration_submission(): void {
        $language = isset($_POST['language']) ? sanitize_key((string) wp_unslash($_POST['language'])) : '';
        if ($this->is_supported_language($language)) {
            $this->current_language = $language;
        }

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => $this->t('ajax.invalidRequest')], 403);
        }

        if ($this->is_submission_rate_limited()) {
            wp_send_json_error(['message' => $this->t('ajax.rateLimited')], 429);
        }

        if ($this->posted_text('website') !== '') {
            // Honeypot: real registrants never see or fill this field. Pretend success
            // without storing anything, so automated fillers don't learn to avoid it.
            wp_send_json_success(['message' => $this->t('ajax.success')]);
        }

        $first_name = $this->posted_text('first_name');
        $last_name = $this->posted_text('last_name');
        $birthday = $this->posted_text('birthday');
        $gender = $this->posted_text('gender');
        $phone = $this->posted_text('phone');
        $email = sanitize_email((string) wp_unslash($_POST['email'] ?? ''));
        $project_id = isset($_POST['project_id']) ? absint($_POST['project_id']) : 0;
        $consent = !empty($_POST['consent']);

        if ($first_name === '' || $last_name === '' || $birthday === '' || $gender === '' || $phone === '' || !is_email($email) || !$project_id || !$consent) {
            wp_send_json_error(['message' => $this->t('ajax.requiredFields')], 400);
        }

        if (!in_array($gender, self::GENDER_OPTIONS, true)) {
            wp_send_json_error(['message' => $this->t('ajax.requiredFields')], 400);
        }

        if (!$this->is_valid_birthday($birthday)) {
            wp_send_json_error(['message' => $this->t('ajax.birthdayError')], 400);
        }

        if (!$this->is_valid_phone($phone)) {
            wp_send_json_error(['message' => $this->t('ajax.phoneError')], 400);
        }

        if (get_post_type($project_id) !== self::POST_TYPE_PROJECT) {
            wp_send_json_error(['message' => $this->t('ajax.projectInvalid')], 400);
        }

        if ($this->registration_exists_for($email, $phone)) {
            wp_send_json_error(['message' => $this->get_already_registered_message()], 409);
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE_REGISTRATION,
            'post_status' => 'publish',
            'post_title' => sprintf('%s %s - %s', $first_name, $last_name, $email),
            'post_content' => '',
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $this->t('ajax.storageError')], 500);
        }

        update_post_meta((int) $post_id, self::META_PREFIX . 'first_name', $first_name);
        update_post_meta((int) $post_id, self::META_PREFIX . 'last_name', $last_name);
        update_post_meta((int) $post_id, self::META_PREFIX . 'birthday', $birthday);
        update_post_meta((int) $post_id, self::META_PREFIX . 'gender', $gender);
        update_post_meta((int) $post_id, self::META_PREFIX . 'phone', $phone);
        update_post_meta((int) $post_id, self::META_PREFIX . 'email', $email);
        update_post_meta((int) $post_id, self::META_PREFIX . 'project_id', $project_id);
        update_post_meta((int) $post_id, self::META_PREFIX . 'language', $language ?: $this->get_current_language());
        update_post_meta((int) $post_id, self::META_PREFIX . 'consent_agreed', '1');
        update_post_meta((int) $post_id, self::META_PREFIX . 'submitted_at', current_time('mysql'));

        $this->send_confirmation_email((int) $post_id);

        wp_send_json_success(['message' => $this->t('ajax.success')]);
    }

    private function send_confirmation_email(int $post_id): void {
        $email = (string) get_post_meta($post_id, self::META_PREFIX . 'email', true);
        if (!is_email($email)) {
            return;
        }

        $first_name = (string) get_post_meta($post_id, self::META_PREFIX . 'first_name', true);
        $project_id = (int) get_post_meta($post_id, self::META_PREFIX . 'project_id', true);
        $language = (string) get_post_meta($post_id, self::META_PREFIX . 'language', true);
        $language = $this->is_supported_language($language) ? $language : self::DEFAULT_LANGUAGE;

        $replacements = [
            '{name}' => $first_name,
            '{project}' => get_the_title($project_id) ?: self::DEFAULT_PROJECT_TITLE,
            '{site}' => wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
            '{admin_email}' => self::ADMIN_CONTACT_EMAIL,
        ];

        $subject = strtr($this->t_for_language('email.confirmation.subject', $language), $replacements);
        $body = strtr($this->t_for_language('email.confirmation.body', $language), $replacements);
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($email, $subject, $body, $headers);
    }

    public function filter_registration_columns(array $columns): array {
        return [
            'cb' => $columns['cb'] ?? '',
            'title' => 'Registration',
            'nms_email' => 'Email',
            'nms_phone' => 'Phone',
            'nms_gender' => 'Gender',
            'nms_project' => 'Project',
            'date' => $columns['date'] ?? 'Date',
        ];
    }

    public function render_registration_column(string $column, int $post_id): void {
        if ($column === 'nms_email') {
            echo esc_html((string) get_post_meta($post_id, self::META_PREFIX . 'email', true));
            return;
        }

        if ($column === 'nms_phone') {
            echo esc_html((string) get_post_meta($post_id, self::META_PREFIX . 'phone', true));
            return;
        }

        if ($column === 'nms_gender') {
            echo esc_html($this->format_gender((string) get_post_meta($post_id, self::META_PREFIX . 'gender', true)));
            return;
        }

        if ($column === 'nms_project') {
            $project_id = (int) get_post_meta($post_id, self::META_PREFIX . 'project_id', true);
            echo esc_html($project_id ? (get_the_title($project_id) ?: '(deleted project)') : '—');
        }
    }

    private function format_gender(string $gender): string {
        return match ($gender) {
            'female' => 'Female',
            'male' => 'Male',
            default => $gender,
        };
    }

    public function filter_project_columns(array $columns): array {
        return [
            'cb' => $columns['cb'] ?? '',
            'title' => 'Project',
            'nms_start_date' => 'Start date',
            'nms_end_date' => 'End date',
            'nms_active' => 'Currently active',
            'date' => $columns['date'] ?? 'Date',
        ];
    }

    public function render_project_column(string $column, int $post_id): void {
        if ($column === 'nms_start_date') {
            echo esc_html((string) get_post_meta($post_id, self::PROJECT_META_PREFIX . 'start_date', true) ?: '—');
            return;
        }

        if ($column === 'nms_end_date') {
            echo esc_html((string) get_post_meta($post_id, self::PROJECT_META_PREFIX . 'end_date', true) ?: '—');
            return;
        }

        if ($column === 'nms_active') {
            echo $this->is_active_project($post_id) ? '✅' : '—';
        }
    }

    public function render_export_button(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'edit-' . self::POST_TYPE_REGISTRATION) {
            return;
        }

        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            return;
        }

        $url = wp_nonce_url(
            add_query_arg('action', 'nms_export_registrations', admin_url('admin-post.php')),
            'nms_export_registrations'
        );

        echo '<div class="notice notice-info"><p><a class="button button-primary" href="' . esc_url($url) . '">Export all registrations (CSV)</a></p></div>';
    }

    public function handle_export_registrations(): void {
        if (!current_user_can(self::MANAGE_CAPABILITY)) {
            wp_die(esc_html__('You are not allowed to export registrations.', 'nutriminds-survey'), '', ['response' => 403]);
        }

        check_admin_referer('nms_export_registrations');

        $registrations = get_posts([
            'post_type' => self::POST_TYPE_REGISTRATION,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="nutriminds-survey-registrations-' . gmdate('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['First name', 'Last name', 'Birthday', 'Gender', 'Phone', 'Email', 'Project', 'Language', 'Submitted at']);

        foreach ($registrations as $registration) {
            $project_id = (int) get_post_meta($registration->ID, self::META_PREFIX . 'project_id', true);
            fputcsv($output, array_map([$this, 'sanitize_csv_cell'], [
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'first_name', true),
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'last_name', true),
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'birthday', true),
                $this->format_gender((string) get_post_meta($registration->ID, self::META_PREFIX . 'gender', true)),
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'phone', true),
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'email', true),
                $project_id ? (get_the_title($project_id) ?: '(deleted project)') : '',
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'language', true),
                (string) get_post_meta($registration->ID, self::META_PREFIX . 'submitted_at', true),
            ]));
        }

        fclose($output);
        exit;
    }

    /**
     * Neutralizes CSV/spreadsheet formula injection: a cell starting with
     * =, +, -, @, tab, or CR is prefixed with a single quote so Excel/Sheets
     * treat it as text instead of executing it as a formula on open.
     */
    private function sanitize_csv_cell(string $value): string {
        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}

register_activation_hook(__FILE__, [NutriMinds_Survey::class, 'activate']);
new NutriMinds_Survey();
