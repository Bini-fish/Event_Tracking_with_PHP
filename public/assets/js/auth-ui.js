/**
 * Auth UI: password toggles, register live validation, role cards.
 */
(function () {
    function syncPasswordToggle(btn, input) {
        var open = btn.querySelector('.password-toggle-eye--open');
        var closed = btn.querySelector('.password-toggle-eye--closed');
        function render() {
            var obscured = input.type === 'password';
            if (open) open.hidden = obscured;
            if (closed) closed.hidden = !obscured;
            btn.setAttribute('aria-label', obscured ? 'Show password' : 'Hide password');
        }
        btn.addEventListener('click', function () {
            input.type = input.type === 'password' ? 'text' : 'password';
            render();
        });
        render();
    }

    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
        var id = btn.getAttribute('data-password-toggle');
        var input = id && document.getElementById(id);
        if (input) syncPasswordToggle(btn, input);
    });

    function setFieldStatus(field, state) {
        var wrap = field.closest('.float-field');
        if (!wrap) return;
        var icon = wrap.querySelector('.field-status-icon');
        wrap.classList.remove('is-valid', 'is-invalid');
        if (icon) {
            icon.innerHTML = '';
            icon.classList.remove('field-status-icon--ok', 'field-status-icon--bad');
        }
        if (state === 'ok') {
            wrap.classList.add('is-valid');
            if (icon) {
                icon.innerHTML = '<svg class="ui-icon-svg" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                icon.classList.add('field-status-icon--ok');
            }
        } else if (state === 'bad') {
            wrap.classList.add('is-invalid');
            if (icon) {
                icon.innerHTML = '<svg class="ui-icon-svg" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.75"/><path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
                icon.classList.add('field-status-icon--bad');
            }
        }
    }

    function validateRegisterField(field) {
        var v = field.value.trim();

        if (field.hasAttribute('required') && v === '') {
            setFieldStatus(field, '');
            return;
        }

        if (field.type === 'email' && v) {
            var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
            setFieldStatus(field, emailOk ? 'ok' : 'bad');
            return;
        }

        if (field.getAttribute('data-validate-password') === '1' && v) {
            var okPw = v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v);
            setFieldStatus(field, okPw ? 'ok' : 'bad');
            return;
        }

        var sel = field.getAttribute('data-validate-confirm');
        if (sel && v) {
            var other = document.querySelector(sel);
            if (!other) return;
            var match = v === other.value && v.length > 0;
            setFieldStatus(field, match ? 'ok' : 'bad');
            return;
        }

        if (field.name === 'name' && v.length >= 2) {
            setFieldStatus(field, 'ok');
            return;
        }

        if (v) setFieldStatus(field, 'ok');
    }

    var regForm = document.getElementById('regForm');
    if (regForm) {
        regForm.querySelectorAll('.js-register-field').forEach(function (field) {
            ['input', 'blur', 'change'].forEach(function (ev) {
                field.addEventListener(ev, function () {
                    validateRegisterField(field);
                });
            });
        });
        var pw = document.getElementById('reg-password');
        var cf = document.getElementById('reg-confirm');
        if (pw && cf) {
            pw.addEventListener('input', function () {
                if (cf.value) validateRegisterField(cf);
            });
        }

        regForm.querySelectorAll('.auth-role-option input[type="radio"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                regForm.querySelectorAll('.auth-role-option').forEach(function (lbl) {
                    var inp = lbl.querySelector('input');
                    lbl.classList.toggle('is-selected', inp && inp.checked);
                });
            });
        });
    }
})();
