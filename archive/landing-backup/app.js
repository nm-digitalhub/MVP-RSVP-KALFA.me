/**
 * kalfa.me — Landing v3
 * Tabs (product preview), 2-step form, validation, copy email, smooth scroll
 */
(function () {
  'use strict';

  // ——— Smooth scroll for in-page anchors ———
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    var id = a.getAttribute('href');
    if (id === '#') return;
    a.addEventListener('click', function (e) {
      var el = document.querySelector(id);
      if (el) {
        e.preventDefault();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ——— Product preview tabs ———
  var tabButtons = document.querySelectorAll('[data-tab]');
  var panels = document.querySelectorAll('[data-panel]');

  function setActiveTab(activeKey) {
    tabButtons.forEach(function (btn) {
      var key = btn.getAttribute('data-tab');
      var isActive = key === activeKey;
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      btn.classList.toggle('text-brand-600', isActive);
      btn.classList.toggle('dark:text-brand-400', isActive);
      btn.classList.toggle('border-brand-500', isActive);
      btn.classList.toggle('bg-white', isActive);
      btn.classList.toggle('dark:bg-gray-800/50', isActive);
      btn.classList.toggle('text-gray-500', !isActive);
      btn.classList.toggle('dark:text-gray-400', !isActive);
      btn.classList.toggle('border-transparent', !isActive);
    });
    panels.forEach(function (panel) {
      var key = panel.getAttribute('data-panel');
      panel.classList.toggle('hidden', key !== activeKey);
    });
  }

  tabButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setActiveTab(btn.getAttribute('data-tab'));
    });
    btn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setActiveTab(btn.getAttribute('data-tab'));
      }
    });
  });

  // ——— 2-step form ———
  var form = document.getElementById('early-access-form');
  var step1 = document.getElementById('form-step-1');
  var step2 = document.getElementById('form-step-2');
  var nextBtn = document.getElementById('form-next-btn');
  var submitBtn = document.getElementById('submit-btn');
  var successEl = document.getElementById('form-success');
  var nameInput = document.getElementById('name');
  var emailInput = document.getElementById('email');
  var nameError = document.getElementById('name-error');
  var emailError = document.getElementById('email-error');

  function clearErrors() {
    if (nameError) nameError.textContent = '';
    if (emailError) emailError.textContent = '';
    if (nameInput) nameInput.setAttribute('aria-invalid', 'false');
    if (emailInput) emailInput.setAttribute('aria-invalid', 'false');
  }

  function showError(input, message) {
    var errEl = input && document.getElementById(input.id + '-error');
    if (errEl) errEl.textContent = message;
    if (input) input.setAttribute('aria-invalid', 'true');
  }

  function validateStep1() {
    clearErrors();
    var valid = true;
    var name = nameInput ? nameInput.value.trim() : '';
    if (!name) {
      showError(nameInput, 'נא להזין שם מלא.');
      valid = false;
    }
    var email = emailInput ? emailInput.value.trim() : '';
    if (!email) {
      showError(emailInput, 'נא להזין אימייל.');
      valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError(emailInput, 'נא להזין כתובת אימייל תקינה.');
      valid = false;
    }
    return valid;
  }

  if (nextBtn && step1 && step2) {
    nextBtn.addEventListener('click', function () {
      if (!validateStep1()) return;
      step1.classList.add('hidden');
      step2.classList.remove('hidden');
    });
  }

  if (form && successEl) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!step2.classList.contains('hidden')) {
        if (!validateStep1()) {
          step1.classList.remove('hidden');
          step2.classList.add('hidden');
          return;
        }
      } else {
        if (!validateStep1()) return;
      }
      form.classList.add('hidden');
      successEl.classList.remove('hidden');
      successEl.setAttribute('aria-live', 'polite');
      if (submitBtn) submitBtn.disabled = true;
    });
  }

  // ——— Copy email ———
  var copyBtn = document.getElementById('copy-email');
  var contactEmail = document.getElementById('contact-email');

  if (copyBtn && contactEmail) {
    copyBtn.addEventListener('click', function () {
      var email = contactEmail.textContent.trim();
      if (typeof navigator.clipboard !== 'undefined' && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(email).then(function () {
          copyBtn.textContent = 'הועתק!';
          setTimeout(function () { copyBtn.textContent = 'העתק'; }, 2000);
        }).catch(function () { fallbackCopy(email); });
      } else {
        fallbackCopy(email);
      }
    });
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      if (copyBtn) {
        copyBtn.textContent = 'הועתק!';
        setTimeout(function () { copyBtn.textContent = 'העתק'; }, 2000);
      }
    } catch (err) {}
    document.body.removeChild(ta);
  }
})();
