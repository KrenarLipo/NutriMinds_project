(function () {
    function readConfig() {
        if (window.NutriMindsSurvey && window.NutriMindsSurvey.projects) {
            return window.NutriMindsSurvey;
        }

        const configNode = document.querySelector('[data-nms-survey-config]');
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

    function label(key, fallback) {
        return text[key] || fallback;
    }

    function initSurvey(root) {
        const form = root.querySelector('form');
        const submitButton = root.querySelector('[data-nms-submit]');
        const notice = root.querySelector('[data-nms-notice]');

        function errorIdFor(field) {
            return `nms-error-${field.name || field.type}`;
        }

        function clearFieldError(field) {
            const container = field.closest('label') || field.parentElement;
            if (!container) {
                return;
            }

            const existing = container.querySelector(`[data-nms-field-error="${field.name}"]`);
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
            error.className = 'nms-field-error';
            error.id = errorIdFor(field);
            error.dataset.nmsFieldError = field.name;
            error.textContent = message;
            container.appendChild(error);
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', error.id);
        }

        function isValidPhone(value) {
            const digits = value.replace(/\D/g, '');

            return /^\+?[\d\s().-]{7,24}$/.test(value) && digits.length >= 7 && digits.length <= 20;
        }

        function isValidBirthday(value) {
            if (!value) {
                return false;
            }

            const date = new Date(value + 'T00:00:00');
            if (Number.isNaN(date.getTime())) {
                return false;
            }

            return date.getTime() <= Date.now();
        }

        function validationMessage(field) {
            const value = field.value.trim();

            if (field.type === 'checkbox') {
                return field.checked ? '' : label('validation.consentRequired', 'Please confirm this consent before submitting.');
            }

            if (field.required && !value) {
                return field.name === 'project_id'
                    ? label('validation.projectRequired', 'Please choose a project.')
                    : label('validation.required', 'This field is required.');
            }

            if (field.type === 'email' && value && !field.checkValidity()) {
                return label('validation.email', 'Please enter a valid email address.');
            }

            if (field.type === 'tel' && value && !isValidPhone(value)) {
                return label('validation.phone', 'Please enter a valid phone number.');
            }

            if (field.name === 'birthday' && value && !isValidBirthday(value)) {
                return label('validation.birthday', 'Please enter a valid date of birth.');
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

        function validateForm() {
            const fields = Array.from(form.querySelectorAll('input[required], select[required]'));
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
            payload.append('action', config.action || 'nms_submit_registration');
            payload.append('nonce', config.nonce || '');
            payload.append('language', config.language || '');

            return payload;
        }

        form.addEventListener('input', (event) => {
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
                clearFieldError(event.target);
            }
        });
        form.addEventListener('change', (event) => {
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
                validateField(event.target);
            }
        });
        form.addEventListener('blur', (event) => {
            if ((event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) && event.target.required) {
                validateField(event.target);
            }
        }, true);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateForm()) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = label('js.submitting', 'Sending...');

            try {
                const response = await fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: getSubmitPayload(),
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : label('js.submitError', 'Submission failed. Please try again.'));
                }

                setNotice(result.data.message || label('js.submitError', 'Registration received.'), 'success');
                form.querySelectorAll('input, select, button').forEach((field) => {
                    if (field !== submitButton) {
                        field.disabled = true;
                    }
                });
                submitButton.remove();
            } catch (error) {
                submitButton.disabled = false;
                submitButton.textContent = label('button.submit', 'Register');
                setNotice(error.message || label('js.submitError', 'Submission failed. Please try again.'), 'error');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-nms-survey]').forEach(initSurvey);
    });
})();
