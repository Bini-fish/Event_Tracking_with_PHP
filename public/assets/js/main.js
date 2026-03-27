// Initialize simple client-side behavior for the app.

/**
 * Attach basic validation styling to auth forms.
 */
function initAuthFormValidation() {
    var forms = document.querySelectorAll('.auth-form');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var invalid = false;

            form.querySelectorAll('input[required]').forEach(function (input) {
                if (!input.value.trim()) {
                    invalid = true;
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });

            if (invalid) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Run all initializers on DOM ready.
 */
function boot() {
    initAuthFormValidation();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

