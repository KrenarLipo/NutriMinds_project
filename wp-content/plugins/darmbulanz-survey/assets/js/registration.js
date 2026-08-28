(function () {
    function readConfig() {
        if (window.DarmbulanzSurvey && window.DarmbulanzSurvey.specialtiesByCategory) {
            return window.DarmbulanzSurvey;
        }

        const configNode = document.querySelector('[data-db-survey-config]');
        if (!configNode) {
            return {};
        }

        try {
            return JSON.parse(configNode.textContent);
        } catch (error) {
            return {};
        }
    }

    const config = readConfig();
    const text = config.text || {};
    const specialtiesByCategory = config.specialtiesByCategory || {};
    const maxFiles = config.maxFiles || 5;
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const maxFileSizeBytes = 10 * 1024 * 1024;

    function label(key, fallback) {
        return text[key] || fallback;
    }

    function initSurvey(root) {
        const form = root.querySelector('form');
        const submitButton = root.querySelector('[data-db-submit]');
        const notice = root.querySelector('[data-db-notice]');
        const categorySelect = root.querySelector('[data-db-main-category]');
        const specialtySelect = root.querySelector('[data-db-specialty]');
        const uploadInput = root.querySelector('[data-db-upload]');

        function populateSpecialties(categoryId, preserveValue) {
            const specialties = specialtiesByCategory[categoryId] || {};
            const keys = Object.keys(specialties);

            specialtySelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = keys.length
                ? label('field.selectPlaceholder', 'Bitte wählen…')
                : label('field.specialtyPlaceholder', 'Bitte zuerst Hauptkategorie wählen');
            specialtySelect.appendChild(placeholder);

            keys.forEach((specialtyId) => {
                const option = document.createElement('option');
                option.value = specialtyId;
                option.textContent = specialties[specialtyId];
                if (specialtyId === preserveValue) {
                    option.selected = true;
                }
                specialtySelect.appendChild(option);
            });

            specialtySelect.disabled = keys.length === 0;
        }

        categorySelect.addEventListener('change', () => {
            populateSpecialties(categorySelect.value, '');
            clearFieldError(specialtySelect);
            updateSubmitState();
        });

        function errorIdFor(field) {
            return `db-error-${field.name || field.type}`;
        }

        function clearFieldError(field) {
            const container = field.closest('label') || field.parentElement;
            if (!container) {
                return;
            }

            const existing = container.querySelector(`[data-db-field-error="${field.name}"]`);
            if (existing) {
                existing.remove();
            }
            field.removeAttribute('aria-invalid');
            field.removeAttribute('aria-describedby');
        }

        function setFieldError(field, message) {
            const container = field.closest('label') || field.parentElement;
            if (!container) {
                return;
            }

            clearFieldError(field);
            const error = document.createElement('small');
            error.className = 'db-field-error';
            error.id = errorIdFor(field);
            error.dataset.dbFieldError = field.name;
            error.textContent = message;
            container.appendChild(error);
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', error.id);
        }

        function isValidPhone(value) {
            const digits = value.replace(/\D/g, '');

            return /^\+?[\d\s().-]{7,24}$/.test(value) && digits.length >= 7 && digits.length <= 20;
        }

        function validateUploadField() {
            const files = Array.from(uploadInput.files || []);

            if (files.length === 0) {
                return label('validation.fileRequired', 'Bitte lade mindestens ein Dokument hoch.');
            }

            if (files.length > maxFiles) {
                return label('validation.tooManyFiles', 'Bitte maximal ' + maxFiles + ' Dateien hochladen.');
            }

            for (const file of files) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!allowedExtensions.includes(ext)) {
                    return label('validation.fileType', 'Nur PDF-, JPG- oder PNG-Dateien sind erlaubt.');
                }
                if (file.size > maxFileSizeBytes) {
                    return label('validation.fileSize', 'Diese Datei ist zu groß (max. 10 MB).');
                }
            }

            return '';
        }

        function validationMessage(field) {
            if (field === uploadInput) {
                return validateUploadField();
            }

            const value = field.value.trim();

            if (field.type === 'checkbox') {
                return field.checked ? '' : label('validation.consentRequired', 'Bitte bestätige die Datenschutzerklärung.');
            }

            if (field.required && !value) {
                if (field.name === 'main_category') {
                    return label('validation.categoryRequired', 'Bitte wähle eine Hauptkategorie.');
                }
                if (field.name === 'specialty') {
                    return label('validation.specialtyRequired', 'Bitte wähle eine Fachrichtung.');
                }
                return label('validation.required', 'Dieses Feld ist erforderlich.');
            }

            if (field.type === 'email' && value && !field.checkValidity()) {
                return label('validation.email', 'Bitte eine gültige E-Mail-Adresse eingeben.');
            }

            if (field.type === 'tel' && value && !isValidPhone(value)) {
                return label('validation.phone', 'Bitte eine gültige Telefonnummer eingeben.');
            }

            return '';
        }

        function validateField(field) {
            const message = validationMessage(field);
            if (message) {
                setFieldError(field, message);
                return false;
            }

            clearFieldError(field);
            return true;
        }

        function requiredFields() {
            return Array.from(form.querySelectorAll('input[required], select[required]')).concat([uploadInput]);
        }

        function validateForm() {
            const fields = requiredFields();
            let firstInvalid = null;

            fields.forEach((field) => {
                if (!validateField(field) && !firstInvalid) {
                    firstInvalid = field;
                }
            });

            if (firstInvalid) {
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            return true;
        }

        function isFormReady() {
            return requiredFields().every((field) => validationMessage(field) === '');
        }

        function updateSubmitState() {
            const ready = isFormReady();
            submitButton.disabled = !ready;
            submitButton.classList.toggle('is-ready', ready);
        }

        function setNotice(message, type) {
            if (!notice) {
                return;
            }
            notice.textContent = message;
            notice.hidden = false;
            notice.classList.toggle('is-error', type === 'error');
            notice.classList.toggle('is-success', type === 'success');
        }

        function getSubmitPayload() {
            const payload = new FormData(form);
            payload.append('action', config.action || 'db_submit_application');
            payload.append('nonce', config.nonce || '');
            payload.append('language', config.language || '');

            return payload;
        }

        form.addEventListener('input', (event) => {
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement || event.target instanceof HTMLTextAreaElement) {
                clearFieldError(event.target);
                updateSubmitState();
            }
        });
        form.addEventListener('change', (event) => {
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
                validateField(event.target);
                updateSubmitState();
            }
        });
        form.addEventListener('blur', (event) => {
            if ((event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) && event.target.required) {
                validateField(event.target);
                updateSubmitState();
            }
        }, true);

        updateSubmitState();

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateForm()) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = label('js.submitting', 'Wird gesendet…');

            try {
                const response = await fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: getSubmitPayload(),
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : label('js.submitError', 'Beim Senden ist ein Fehler aufgetreten. Bitte versuche es erneut.'));
                }

                setNotice(result.data.message || label('js.submitError', 'Danke für deine Anmeldung!'), 'success');
                form.querySelectorAll('input, select, textarea, button').forEach((field) => {
                    if (field !== submitButton) {
                        field.disabled = true;
                    }
                });
                submitButton.remove();
            } catch (error) {
                updateSubmitState();
                submitButton.textContent = label('button.submit', 'Jetzt bewerben');
                setNotice(error.message || label('js.submitError', 'Beim Senden ist ein Fehler aufgetreten. Bitte versuche es erneut.'), 'error');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-db-survey]').forEach(initSurvey);
    });
})();
