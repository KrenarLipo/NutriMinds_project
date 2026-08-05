<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<script type="application/json" data-nms-survey-config>
<?php echo wp_json_encode($nutriminds_survey_config ?? []); ?>
</script>

<section class="nms-survey" data-nms-survey>
    <div class="nms-survey__shell">
        <header class="nms-survey__header">
            <div>
                <h1><?php echo esc_html($this->t('form.title')); ?></h1>
                <p><?php echo esc_html($this->t('form.intro')); ?></p>
            </div>
            <?php echo $this->render_language_switcher(); ?>
        </header>

        <div class="nms-survey__notice" data-nms-notice hidden role="alert" aria-live="assertive"></div>

        <?php if (empty($nutriminds_survey_config['projects'])) : ?>
            <p class="nms-survey__empty"><?php echo esc_html($this->t('form.noProjects')); ?></p>
        <?php else : ?>
        <form class="nms-survey__form" novalidate>
            <label class="nms-hp-field" aria-hidden="true">
                Website
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>

            <div class="nms-grid nms-grid--two">
                <label>
                    <span><?php echo esc_html($this->t('field.firstName')); ?></span>
                    <input type="text" name="first_name" autocomplete="given-name" required>
                </label>
                <label>
                    <span><?php echo esc_html($this->t('field.lastName')); ?></span>
                    <input type="text" name="last_name" autocomplete="family-name" required>
                </label>
            </div>

            <div class="nms-grid nms-grid--two">
                <label>
                    <span><?php echo esc_html($this->t('field.birthday')); ?></span>
                    <input type="date" name="birthday" autocomplete="bday" required>
                </label>
                <label>
                    <span><?php echo esc_html($this->t('field.gender')); ?></span>
                    <select name="gender" required>
                        <option value=""><?php echo esc_html($this->t('field.genderSelect')); ?></option>
                        <option value="female"><?php echo esc_html($this->t('gender.female')); ?></option>
                        <option value="male"><?php echo esc_html($this->t('gender.male')); ?></option>
                    </select>
                </label>
            </div>

            <div class="nms-grid nms-grid--two">
                <label>
                    <span><?php echo esc_html($this->t('field.phone')); ?></span>
                    <input type="tel" name="phone" autocomplete="tel" required>
                </label>
                <label>
                    <span><?php echo esc_html($this->t('field.email')); ?></span>
                    <input type="email" name="email" autocomplete="email" required>
                </label>
            </div>

            <label class="nms-field--full">
                <span><?php echo esc_html($this->t('field.project')); ?></span>
                <select name="project_id" required>
                    <option value=""><?php echo esc_html($this->t('field.projectSelect')); ?></option>
                    <?php foreach ($nutriminds_survey_config['projects'] ?? [] as $project) : ?>
                        <option value="<?php echo esc_attr((string) $project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="nms-check">
                <input type="checkbox" name="consent" required>
                <span><?php echo wp_kses($this->get_consent_label(), ['a' => ['href' => [], 'target' => [], 'rel' => []]]); ?></span>
            </label>

            <button type="submit" class="nms-button nms-button--primary" data-nms-submit><?php echo esc_html($this->t('button.submit')); ?></button>
        </form>
        <?php endif; ?>
    </div>
</section>
