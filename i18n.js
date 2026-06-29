(function () {
  var STORAGE_KEY = 'giic_lang';
  var dictCache = {};

  function getByPath(obj, path) {
    return path.split('.').reduce(function (acc, key) {
      return acc && acc[key] !== undefined ? acc[key] : null;
    }, obj);
  }

  function applyDict(dict) {
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      var val = getByPath(dict, el.getAttribute('data-i18n'));
      if (val !== null) el.textContent = val;
    });
    document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
      var val = getByPath(dict, el.getAttribute('data-i18n-html'));
      if (val !== null) el.innerHTML = val;
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
      var val = getByPath(dict, el.getAttribute('data-i18n-placeholder'));
      if (val !== null) el.setAttribute('placeholder', val);
    });
  }

  function setLang(lang) {
    if (dictCache[lang]) {
      finishSetLang(lang, dictCache[lang]);
      return;
    }
    fetch('i18n/' + lang + '.json')
      .then(function (res) { return res.json(); })
      .then(function (dict) {
        dictCache[lang] = dict;
        finishSetLang(lang, dict);
      })
      .catch(function () { /* leave current content as-is on failure */ });
  }

  function finishSetLang(lang, dict) {
    applyDict(dict);
    document.documentElement.setAttribute('lang', lang);
    localStorage.setItem(STORAGE_KEY, lang);
    var btn = document.getElementById('langToggle');
    if (btn) btn.textContent = lang === 'de' ? 'EN' : 'DE';
    var formLang = document.getElementById('formLang');
    if (formLang) formLang.value = lang;
  }

  var CONSENT_KEY = 'giic_cookie_consent';

  function initCookieBanner() {
    var banner = document.getElementById('cookieBanner');
    if (!banner) return;

    if (!localStorage.getItem(CONSENT_KEY)) {
      banner.classList.add('show');
    }

    function decide(value) {
      localStorage.setItem(CONSENT_KEY, value);
      banner.classList.remove('show');

      var lang = localStorage.getItem(STORAGE_KEY) || 'de';
      fetch('api/consent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ consent: value, lang: lang }),
      }).catch(function () { /* consent is still kept locally even if logging fails */ });
    }

    var acceptBtn = document.getElementById('cookieAccept');
    var rejectBtn = document.getElementById('cookieReject');
    if (acceptBtn) acceptBtn.addEventListener('click', function () { decide('accepted'); });
    if (rejectBtn) rejectBtn.addEventListener('click', function () { decide('rejected'); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var saved = localStorage.getItem(STORAGE_KEY) || 'de';
    setLang(saved);
    initCookieBanner();

    var btn = document.getElementById('langToggle');
    if (btn) {
      btn.addEventListener('click', function () {
        var current = localStorage.getItem(STORAGE_KEY) || 'de';
        setLang(current === 'de' ? 'en' : 'de');
      });
    }
  });
})();
