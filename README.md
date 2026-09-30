# Desktop Site Toggle

A small "Desktop site / Mobile site" switcher for responsive websites. It lets phone visitors force the full desktop layout on demand, and switch back just as easily. The choice is remembered across pages with a cookie.

I built this for my personal site because the mobile stacking was hiding the full dashboard layout, and I wanted visitors to have the same "request desktop site" option that browsers and big sites offer.

## The core idea

Everything hinges on one fact: mobile browsers pick which CSS media queries apply based on the layout viewport, and the layout viewport comes from the viewport meta tag. Normally it is `width=device-width`, which is why `@media (max-width: ...)` rules kick in on phones.

If you rewrite that tag to a fixed desktop width, say `width=1280`, before the browser computes layout, the page renders exactly as it would on a 1280px monitor. Every desktop media query applies. No server changes, no separate templates, no user agent sniffing.

The full flow:

1. A toggle button is fixed to the bottom left of every page.
2. Tapping **Desktop site** writes a `bolu_desktop=1` cookie (30 days) and reloads.
3. A script placed at the very top of `<head>` reads the cookie before first paint. When set, it rewrites the viewport meta to `width=1280` and adds a `force-desktop` class to `<html>`.
4. Tapping **Mobile site** deletes the cookie and reloads, restoring `width=device-width`.

## Why I built it this way

I looked at the alternatives first. User agent sniffing on the server is fragile and easy to get wrong. A separate mobile subdomain doubles every future layout change. A pure CSS approach cannot work because media queries read the viewport, not a class. The cookie plus viewport swap is client side, it is a few dozen lines, and it composes with whatever responsive CSS already exists. The reload keeps the logic dead simple: no live re-layout code paths to maintain.

## Implementation

Built with PHP, JavaScript, and CSS. On my site it ships as a WordPress Code Snippets snippet so the theme files stay untouched, but the technique ports to anything that lets you inject into `<head>` and the page footer.

### 1. The early head script (the important part)

```php
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
```

Two details matter here. Priority `1` on `wp_head` puts this before anything else that could trigger layout. And it mutates the existing meta tag rather than adding a second one, because a duplicate viewport tag leaves the behavior up to the browser.

### 2. The toggle button and its handler

```php
add_action('wp_footer', function () {
    ?>
    <button id="bolu-view-toggle" type="button"></button>
    <script>
    (function(){
      var b = document.getElementById('bolu-view-toggle');
      function isDesktop(){ return /(?:^|;\s*)bolu_desktop=1/.test(document.cookie); }
      function paint(){ b.textContent = isDesktop() ? 'Mobile site' : 'Desktop site'; }
      b.addEventListener('click', function(){
        document.cookie = isDesktop()
          ? 'bolu_desktop=; path=/; max-age=0'
          : 'bolu_desktop=1; path=/; max-age=2592000';
        location.reload();
      });
      paint();
    })();
    </script>
    <?php
});
```

Styling is in the full snippet under `src/`. The label always reflects the current state, so the button reads as the action you are about to take.

### 3. The full takeover page edge case

One page on my site renders its own complete HTML document and exits before WordPress loads the theme, so the site wide snippet never executes there. That page carries its own copy of the same logic: the viewport script inlined immediately after its meta tag, and a compact toggle button in its header wired to the same `bolu_desktop` cookie. Same cookie, same behavior, one source of truth for the preference. See `src/full-takeover-example.html` for the portable version.

## Verification

I verified the round trip by hand:

- Toggle to desktop: cookie `bolu_desktop=1` present, viewport meta reads `width=1280`, `<html>` carries `force-desktop`, button label flips to "Mobile site".
- Toggle back: cookie gone, viewport back to `width=device-width`, label back to "Desktop site".
- Confirmed the floating button appears on regular theme pages and the header button appears on the full takeover page, with neither leaking into the other.

## Files

- `src/desktop-site-toggle.php` : the complete site wide snippet (PHP + JS + CSS).
- `src/full-takeover-example.html` : minimal standalone page showing the pattern for documents that bypass the theme.

## Notes

- 1280 is a choice, not a magic number. Pick whatever width your desktop breakpoints are designed around.
- The cookie is intentionally plain JavaScript readable (it has to be, the script reads it) with `path=/` so one toggle covers the whole site.
