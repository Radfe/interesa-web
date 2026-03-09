(function(){
  var btn = document.querySelector('[data-nav-toggle]');
  var nav = document.getElementById('site-nav');
  if(!btn || !nav) return;

  function closeNav(){
    nav.classList.remove('open');
    document.body.style.overflow = '';
    btn.setAttribute('aria-expanded','false');
  }
  function toggle(){
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  btn.addEventListener('click', toggle);
  nav.addEventListener('click', function(e){
    if(e.target.tagName === 'A') closeNav();
  });
  var mq = window.matchMedia('(min-width: 901px)');
  function onResize(){ if (mq.matches) closeNav(); }
  window.addEventListener('resize', onResize);
  onResize();
})();
