<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<script type="application/json" data-db-survey-config>
<?php echo wp_json_encode($darmbulanz_survey_config ?? []); ?>
</script>

<section class="db-survey" data-db-survey>
    <div class="db-survey__shell">
        <header class="db-survey__header">
            <div class="db-survey__header-text">
                <h1><?php echo esc_html($this->t('form.title')); ?></h1>
                <p><?php echo esc_html($this->t('form.intro')); ?></p>
            </div>
            <?php echo $this->render_language_switcher(); ?>
        </header>

        <p class="db-survey__help-note"><?php echo esc_html($this->t('form.helpNote')); ?></p>

        <div class="db-survey__notice" data-db-notice hidden role="alert" aria-live="assertive"></div>

        <form class="db-survey__form" novalidate enctype="multipart/form-data">
            <label class="db-hp-field" aria-hidden="true">
                Website
                <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
            </label>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.personal.title')); ?></h2>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.salutation')); ?> *</span>
                        <select name="salutation" required>
                            <option value=""><?php echo esc_html($this->t('field.selectPlaceholder')); ?></option>
                            <?php foreach ($this->get_salutations() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.title')); ?></span>
                        <select name="title">
                            <?php foreach ($this->get_titles() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.firstName')); ?> *</span>
                        <input type="text" name="first_name" autocomplete="given-name" required>
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.lastName')); ?> *</span>
                        <input type="text" name="last_name" autocomplete="family-name" required>
                    </label>
                </div>
            </div>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.professional.title')); ?></h2>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.mainCategory')); ?> *</span>
                        <select name="main_category" data-db-main-category required>
                            <option value=""><?php echo esc_html($this->t('field.selectPlaceholder')); ?></option>
                            <?php foreach ($this->get_main_categories() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.specialty')); ?> *</span>
                        <select name="specialty" data-db-specialty required disabled>
                            <option value=""><?php echo esc_html($this->t('field.specialtyPlaceholder')); ?></option>
                        </select>
                    </label>
                </div>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.additionalQualifications')); ?></span>
                        <input type="text" name="additional_qualifications">
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.licenseNumber')); ?></span>
                        <input type="text" name="license_number">
                    </label>
                </div>
                <label class="db-field--full">
                    <span><?php echo esc_html($this->t('field.upload')); ?> *</span>
                    <input type="file" name="documents[]" data-db-upload multiple accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="db-field-hint"><?php echo esc_html($this->t('field.uploadHint')); ?></small>
                </label>
            </div>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.institution.title')); ?></h2>
                <label class="db-field--full">
                    <span><?php echo esc_html($this->t('field.institution')); ?> *</span>
                    <input type="text" name="institution" required>
                </label>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.street')); ?></span>
                        <input type="text" name="street" autocomplete="street-address">
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.postalCode')); ?></span>
                        <input type="text" name="postal_code" autocomplete="postal-code">
                    </label>
                </div>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.city')); ?></span>
                        <input type="text" name="city" autocomplete="address-level2">
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.country')); ?> *</span>
                        <select name="country" required>
                            <option value=""><?php echo esc_html($this->t('field.selectPlaceholder')); ?></option>
                            <?php foreach ($this->get_countries() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.contact.title')); ?></h2>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.phone')); ?> *</span>
                        <input type="tel" name="phone" autocomplete="tel" required>
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.email')); ?> *</span>
                        <input type="email" name="email" autocomplete="email" required>
                    </label>
                </div>
                <div class="db-grid db-grid--two">
                    <label>
                        <span><?php echo esc_html($this->t('field.website')); ?></span>
                        <input type="url" name="website" autocomplete="url" placeholder="https://">
                    </label>
                    <label>
                        <span><?php echo esc_html($this->t('field.socialMedia')); ?></span>
                        <input type="text" name="social_media">
                    </label>
                </div>
            </div>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.interest.title')); ?></h2>
                <label class="db-field--full">
                    <span><?php echo esc_html($this->t('field.focusAreas')); ?></span>
                    <textarea name="focus_areas" rows="2"></textarea>
                </label>
                <h2><?php echo esc_html($this->t('section.interest.subtitle')); ?></h2>
                <div class="db-grid db-grid--two">
                    <label class="db-check">
                        <input type="checkbox" name="interest_referral">
                        <span><?php echo esc_html($this->t('field.interestReferral')); ?></span>
                    </label>
                    <label class="db-check">
                        <input type="checkbox" name="interest_content">
                        <span><?php echo esc_html($this->t('field.interestContent')); ?></span>
                    </label>
                    <label class="db-check">
                        <input type="checkbox" name="interest_training">
                        <span><?php echo esc_html($this->t('field.interestTraining')); ?></span>
                    </label>
                    <label class="db-check">
                        <input type="checkbox" name="interest_research">
                        <span><?php echo esc_html($this->t('field.interestResearch')); ?></span>
                    </label>
                </div>
                <label class="db-field--full">
                    <span><?php echo esc_html($this->t('field.remarks')); ?></span>
                    <textarea name="remarks" rows="2"></textarea>
                </label>
            </div>

            <div class="db-section">
                <h2><?php echo esc_html($this->t('section.consent.title')); ?></h2>
                <label class="db-check">
                    <input type="checkbox" name="consent" required>
                    <span><?php echo wp_kses($this->get_consent_label(), ['a' => ['href' => [], 'target' => [], 'rel' => []]]); ?> *</span>
                </label>
            </div>

            <button type="submit" class="db-button db-button--primary" data-db-submit><?php echo esc_html($this->t('button.submit')); ?></button>
        </form>
    </div>
</section>
