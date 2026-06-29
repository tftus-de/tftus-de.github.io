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
  }

  function setLang(lang) {
    if (dictCache[lang]) {
      finishSetLang(lang, dictCache[lang]);
      return;
    }
    fetch('../i18n/legal.' + lang + '.json')
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
    if (typeof window.rebuildLegalToc === 'function') window.rebuildLegalToc();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var saved = localStorage.getItem(STORAGE_KEY) || 'de';
    setLang(saved);

    var btn = document.getElementById('langToggle');
    if (btn) {
      btn.addEventListener('click', function () {
        var current = localStorage.getItem(STORAGE_KEY) || 'de';
        setLang(current === 'de' ? 'en' : 'de');
      });
    }
  });
})();
