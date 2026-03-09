(function(){
  function ready(fn){ (document.readyState !== 'loading') ? fn() : document.addEventListener('DOMContentLoaded', fn); }

  ready(function(){
    var f = document.getElementById('b12frame');
    if(!f) return;

    function resize(){
      try {
        var doc = f.contentWindow.document;
        var h = Math.max(
          doc.body.scrollHeight,
          doc.documentElement.scrollHeight
        );
        if (h && h !== f._h){ f.style.height = h + 'px'; f._h = h; }
      } catch(e){ /* nič – ak by iframe náhodou nebol same-origin */ }
    }
    f.addEventListener('load', resize);
    resize();
    setInterval(resize, 800);
  });
})();
