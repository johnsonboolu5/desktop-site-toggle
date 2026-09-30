<?php
/**
 * Desktop Site Toggle
 *
 * Site-wide "Desktop site / Mobile site" switcher for WordPress.
 * Delivered as a Code Snippets snippet so the theme files stay untouched.
 *
 * How it works: tapping the toggle writes a bolu_desktop cookie and reloads.
 * An early head script (priority 1, before layout) reads the cookie and swaps
 * the viewport meta tag to a fixed desktop width, so phones render the full
 * desktop layout. Tapping again deletes the cookie and restores mobile view.
 *
 * Pages that render their own document and bypass the theme need their own
 * copy of the head script; see full-takeover-example.html for that pattern.
 */

add_action('wp_head', function () {
    ?>
    <script>
    (function(){
      try{
        if(/(?:^|;\s*)bolu_desktop=1/.test(document.cookie)){
          var vp = document.querySelector('meta[name="viewport"]');
          if(vp){ vp.setAttribute('content', 'width=1280'); }
          else {
            var m = document.createElement('meta');
            m.name = 'viewport'; m.content = 'width=1280';
            document.getElementsByTagName('head')[0].appendChild(m);
          }
          document.documentElement.className += ' force-desktop';
        }
      }catch(e){}
    })();
    </script>
    <?php
}, 1);

add_action('wp_footer', function () {
    ?>
    <style>
    #bolu-view-toggle{
      position: fixed; left: 12px; bottom: 12px; z-index: 99999;
      font-family: ui-monospace, SFMono-Regular, monospace;
      font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase;
      background: #fff; border: 1px solid #d9e6de; border-radius: 999px;
      padding: 8px 14px; color: #3c4a44; cursor: pointer;
      box-shadow: 0 2px 10px rgba(0,0,0,.08); opacity: .85;
    }
    #bolu-view-toggle:hover{ opacity: 1; border-color: #17bb85; color: #0b8a5e; }
    </style>
    <button id="bolu-view-toggle" type="button"></button>
    <script>
    (function(){
      var b = document.getElementById('bolu-view-toggle');
      if(!b) return;
      function isDesktop(){ return /(?:^|;\s*)bolu_desktop=1/.test(document.cookie); }
      function paint(){ b.textContent = isDesktop() ? 'Mobile site' : 'Desktop site'; }
      b.addEventListener('click', function(){
        if(isDesktop()){ document.cookie = 'bolu_desktop=; path=/; max-age=0'; }
        else { document.cookie = 'bolu_desktop=1; path=/; max-age=2592000'; }
        location.reload();
      });
      paint();
    })();
    </script>
    <?php
});
