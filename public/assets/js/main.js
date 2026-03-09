(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.querySelector('.menu-toggle');
    var nav = document.getElementById('site-nav');
    if(!btn || !nav) return;

    function closeMenu(){
      nav.classList.remove('open');
      document.body.classList.remove('menu-open');
      btn.setAttribute('aria-expanded','false');
    }
    function openMenu(){
      nav.classList.add('open');
      document.body.classList.add('menu-open');
      btn.setAttribute('aria-expanded','true');
    }
    btn.addEventListener('click', function(){
      var isOpen = nav.classList.contains('open');
      isOpen ? closeMenu() : openMenu();
    });
    document.addEventListener('keydown', function(e){
      if(e.key==='Escape'){ closeMenu(); }
    });
    document.addEventListener('click', function(e){
      if(!nav.classList.contains('open')) return;
      if(e.target.closest('#site-nav') || e.target.closest('.menu-toggle')) return;
      closeMenu();
    });
    // zavrieť po kliknutí na link
    nav.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', closeMenu); });
  });
})();