(function () {
  var scrollBound = false;
  var links = [];

  function buildToc() {
    var content = document.querySelector('.legal-content');
    var tocList = document.getElementById('legalTocList');
    if (!content || !tocList) return;

    tocList.innerHTML = '';
    links = [];

    var headings = content.querySelectorAll('h2');
    headings.forEach(function (h, i) {
      h.id = 'section-' + (i + 1);
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#' + h.id;
      a.textContent = h.textContent;
      li.appendChild(a);
      tocList.appendChild(li);
      links.push({ link: a, heading: h });
    });

    setActive();

    if (!scrollBound) {
      window.addEventListener('scroll', setActive, { passive: true });
      scrollBound = true;
    }
  }

  function setActive() {
    if (!links.length) return;
    var y = window.scrollY + 110;
    var current = links[0];
    links.forEach(function (item) {
      if (item.heading.offsetTop <= y) current = item;
    });
    links.forEach(function (item) {
      item.link.classList.toggle('active', item === current);
    });
  }

  window.rebuildLegalToc = buildToc;

  document.addEventListener('DOMContentLoaded', buildToc);
})();
