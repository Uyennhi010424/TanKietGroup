document.addEventListener('DOMContentLoaded', function(){
  var toggle = document.querySelector('[data-sidebar-toggle]');
  var sidebar = document.querySelector('.admin-sidebar');
  var wrap = document.querySelector('.admin-wrap');
  var overlay = document.querySelector('[data-sidebar-overlay]');

  function closeSidebar(){
    if(wrap) wrap.classList.remove('sidebar-open');
    if(toggle) toggle.classList.remove('open');
  }

  function openSidebar(){
    if(wrap) wrap.classList.add('sidebar-open');
    if(toggle) toggle.classList.add('open');
  }

  if(toggle && sidebar){
    toggle.addEventListener('click', function(){
      if(wrap && wrap.classList.contains('sidebar-open')) closeSidebar(); else openSidebar();
    });
  }

  if(overlay){
    overlay.addEventListener('click', closeSidebar);
  }

  window.addEventListener('resize', function(){
    if (window.innerWidth > 900) closeSidebar();
  });
});
