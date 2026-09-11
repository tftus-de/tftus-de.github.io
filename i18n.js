(function () {
  var STORAGE_KEY = 'giic_lang';
  var dictCache = {};
  var activeLang = null;
  var activeDict = null;

  window.tftI18n = {
    getLang: function () { return activeLang || localStorage.getItem(STORAGE_KEY) || 'de'; },
    getDict: function () { return activeDict; }
  };

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
    fetch('i18n/' + lang + '.json', { cache: 'no-store' })
      .then(function (res) { return res.json(); })
      .then(function (dict) {
        dictCache[lang] = dict;
        finishSetLang(lang, dict);
      })
      .catch(function () { /* leave current content as-is on failure */ });
  }

  function finishSetLang(lang, dict) {
    activeLang = lang;
    activeDict = dict;
    applyDict(dict);
    if(window._sliderSyncFns) window._sliderSyncFns.forEach(function(fn){ fn(); });
    document.documentElement.setAttribute('lang', lang);
    localStorage.setItem(STORAGE_KEY, lang);
    var btn = document.getElementById('langToggle');
    if (btn) btn.setAttribute('aria-pressed', lang === 'en' ? 'true' : 'false');
    var formLang = document.getElementById('formLang');
    if (formLang) formLang.value = lang;
    ckFabSync();
    document.dispatchEvent(new CustomEvent('tft:i18n:change', { detail: { lang: lang, dict: dict } }));
  }

  var CONSENT_KEY = 'giic_cookie_consent_v2';
  var CK_IDS = ['necessary','functional','analytics','marketing','social'];
  var ckState = { necessary: true, functional: false, analytics: false, marketing: false, social: false };

  function ckLoad() {
    try {
      var saved = JSON.parse(localStorage.getItem(CONSENT_KEY));
      if (saved) CK_IDS.forEach(function(id){ if (id !== 'necessary' && saved[id] !== undefined) ckState[id] = !!saved[id]; });
    } catch(e) {}
  }

  function ckPersist(label) {
    var out = {}; CK_IDS.forEach(function(id){ out[id] = ckState[id]; }); out._ts = new Date().toISOString();
    localStorage.setItem(CONSENT_KEY, JSON.stringify(out));
    fetch('api/consent.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ consent: label, state: out, lang: localStorage.getItem(STORAGE_KEY)||'de' }) }).catch(function(){});
  }

  function ckRender() {
    var lang = localStorage.getItem(STORAGE_KEY) || 'de';
    var dict = dictCache[lang] || dictCache['de'] || {};
    var c = (dict.cookies && dict.cookies.categories) || {};
    var hdrs = (dict.cookies && dict.cookies.tableHeaders) || ['Name','Provider','Purpose','Expiry'];
    var showTxt = (dict.cookies && dict.cookies.showCookies) || 'Show cookies';
    var list = document.getElementById('cookieCategoryList');
    if (!list) return;
    list.innerHTML = '';
    CK_IDS.forEach(function(id) {
      var cat = c[id] || {}; var locked = id === 'necessary';
      var rows = (cat.cookies || []).map(function(r){ return '<tr><td>' + [r.name,r.provider,r.purpose,r.expiry].join('</td><td>') + '</td></tr>'; }).join('');
      var el = document.createElement('div'); el.className = 'ck-category';
      el.innerHTML =
        '<div class="ck-category-head"><span class="ck-cat-name">' + (cat.name||id) + '</span>' +
        '<label class="ck-switch"><input type="checkbox" data-id="' + id + '"' + (ckState[id]?' checked':'') + (locked?' disabled':'') + '><span class="ck-slider"></span></label></div>' +
        '<p class="ck-cat-meaning">' + (cat.meaning||'') + '</p>' +
        '<details><summary>' + showTxt + '</summary>' +
        '<table class="ck-cookie-table"><tr><td><b>' + hdrs.join('</b></td><td><b>') + '</b></td></tr>' + rows + '</table></details>';
      list.appendChild(el);
    });
    list.querySelectorAll('input[type=checkbox]').forEach(function(inp){
      inp.addEventListener('change', function(e){ var id = e.target.getAttribute('data-id'); if (id !== 'necessary') ckState[id] = e.target.checked; });
    });
  }

  function ckOpenModal(e) { if (e) e.preventDefault(); ckRender(); var b = document.getElementById('cookieModalBackdrop'); if (b) b.classList.add('open'); ckFabClosePopup(); }
  function ckCloseModal() { var b = document.getElementById('cookieModalBackdrop'); if (b) b.classList.remove('open'); }

  function ckFabClosePopup() { var p = document.getElementById('ckFabPopup'); if (p) p.classList.remove('open'); }

  function ckFabSync() {
    var fab = document.getElementById('ckFab');
    if (!fab) return;
    if (localStorage.getItem(CONSENT_KEY)) {
      fab.classList.add('visible');
    } else {
      fab.classList.remove('visible');
    }
    var lang = localStorage.getItem(STORAGE_KEY) || 'de';
    var dict = (dictCache && dictCache[lang] && dictCache[lang].cookies && dictCache[lang].cookies.categories) || {};
    var container = document.getElementById('ckFabCats');
    if (!container) return;
    container.innerHTML = '';
    CK_IDS.forEach(function(id) {
      var label = (dict[id] && dict[id].name) || id;
      var on = ckState[id];
      var row = document.createElement('div');
      row.className = 'ck-fab-cat';
      row.innerHTML = '<span class="ck-fab-cat-name">' + label + '</span>'
        + '<span class="ck-fab-status ' + (on ? 'on' : 'off') + '">' + (on ? (lang==='de'?'Ja':'Yes') : (lang==='de'?'Nein':'No')) + '</span>';
      container.appendChild(row);
    });
  }

  function initCookieBanner() {
    ckLoad();
    var banner = document.getElementById('cookieBanner');
    if (!banner) return;
    if (!localStorage.getItem(CONSENT_KEY)) banner.classList.add('show');

    function hideBanner() { banner.classList.remove('show'); ckFabSync(); }

    var el = document.getElementById('cookieAccept');
    if (el) el.addEventListener('click', function(){ CK_IDS.forEach(function(id){ ckState[id]=true; }); ckPersist('accepted-all'); hideBanner(); });

    el = document.getElementById('cookieReject');
    if (el) el.addEventListener('click', function(){ CK_IDS.forEach(function(id){ if(id!=='necessary') ckState[id]=false; }); ckPersist('rejected-all'); hideBanner(); });

    el = document.getElementById('cookieCustomize');
    if (el) el.addEventListener('click', ckOpenModal);

    el = document.getElementById('ckModalAcceptAll');
    if (el) el.addEventListener('click', function(){ CK_IDS.forEach(function(id){ ckState[id]=true; }); ckRender(); });

    el = document.getElementById('ckModalReject');
    if (el) el.addEventListener('click', function(){ CK_IDS.forEach(function(id){ if(id!=='necessary') ckState[id]=false; }); ckRender(); });

    el = document.getElementById('ckModalSave');
    if (el) el.addEventListener('click', function(){
      ckPersist('custom'); hideBanner();
      var conf = document.getElementById('ckSaveConfirm');
      if (conf) { conf.style.display='block'; setTimeout(function(){ conf.style.display='none'; ckCloseModal(); ckFabSync(); }, 1200); }
      else { ckCloseModal(); ckFabSync(); }
    });

    var backdrop = document.getElementById('cookieModalBackdrop');
    if (backdrop) backdrop.addEventListener('click', function(e){ if (e.target===backdrop) ckCloseModal(); });

    document.querySelectorAll('[data-cookie-settings]').forEach(function(a){
      a.addEventListener('click', ckOpenModal);
    });

    // FAB wiring
    var fab = document.getElementById('ckFab');
    var fabPopup = document.getElementById('ckFabPopup');
    if (fab) {
      ckFabSync();
      fab.addEventListener('click', function(e) {
        e.stopPropagation();
        if (fabPopup) fabPopup.classList.toggle('open');
        ckFabSync();
      });
    }
    el = document.getElementById('ckFabCustomize');
    if (el) el.addEventListener('click', ckOpenModal);

    document.addEventListener('click', function(e) {
      if (fabPopup && fabPopup.classList.contains('open') && !fabPopup.contains(e.target) && e.target !== fab) {
        ckFabClosePopup();
      }
    });
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
