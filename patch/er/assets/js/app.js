// INTERESA – app.js (minimal)
// 1) GA4 event pri kliknutí na /go/<slug>
// 2) Bez blokovania navigácie (gtag používa 'beacon')

(function(){
  document.addEventListener('click', function(e){
    var a = e.target.closest && e.target.closest('a[href^="/go/"]');
    if(!a) return;
    var slug = a.getAttribute('data-slug') || (a.pathname.split('/')[2] || '');
    if (window.gtag) {
      window.gtag('event', 'affiliate_click', {
        aff_slug: slug,
        link_url: a.href
      });
    }
  }, {capture:true});
})();
