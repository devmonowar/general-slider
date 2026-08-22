=== General Slider ===
Contributors: kstmonowar, ksthannan
Tags: slider, carousel, elementor, testimonial, slideshow
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.3.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create beautiful, reusable carousel sliders in minutes — lightweight, block-ready, and no coding required.

== Description ==

General Slider is a fast, modern slider for WordPress. Create a slider once, then place it anywhere with a block or shortcode — no page builder and no coding required.

Use it as a hero slider, image carousel, testimonial slider, logo carousel, product showcase or fullscreen banner, in the block editor, with a shortcode, or as an Elementor widget — on any block or classic theme. It's ideal for WooCommerce product sliders, Full Site Editing layouts, portfolios and landing pages.

**[Browse the demo gallery](https://devmonowar.github.io/wp-plugin-demo-library/general-slider/)** — see every ready-made slider you can import in one click · **[Read the full guide](https://devmonowar.github.io/blog/general-slider-fast-accessible-wordpress-slider/)** — setting up a slider, the block and shortcode options, and how the assets stay off pages that have no slider · **[Development on GitHub](https://github.com/devmonowar/general-slider)** — report issues or contribute.

= Why General Slider? =

* **Lightweight and fast** — no jQuery on the front end (built on the Splide engine)
* **Accessibility-first** — keyboard, screen reader, play/pause and reduced-motion support
* **Works with any theme** — block (FSE) or classic
* **Beginner friendly** — build sliders from the WordPress admin, no code
* **Block, shortcode and Elementor** — add a slider anywhere
* **Loads only where needed** — assets are added only on pages that show a slider

= Perfect for =

* Business and agency websites
* Portfolios and landing pages
* Hero sections and fullscreen banners
* Image and logo carousels
* Testimonials and team showcases
* Product showcases and WooCommerce banners

= Getting started =

1. Install and activate General Slider.
2. Go to **General Slider > Add New**, add your slides, or import a ready-made demo from the Demo Library.
3. Add the **General Slider** block to any page, or paste the shortcode. Done.

= Features =

**Editing**

* Reusable sliders — build slides manually, or pull them dynamically from your posts, pages or WooCommerce products
* Per-slide image or background video (self-hosted MP4/WebM, YouTube or Vimeo)
* Sub heading, heading, text, button and a whole-slide clickable link
* Categories, a duplicate action and custom CSS per slider
* Start from a ready-made demo when creating a new slider

**Display**

* Gutenberg block, shortcode and Elementor widget
* Five design presets — Hero, Split, Minimal, Testimonial and Fullscreen
* Navigation & frame skins — Classic, Soft, Stories, Stories Progress (auto-filling segments), Numbers, Vertical, Corner, Neon, Minimal, Pill, Outline, Retro, Glass and Dark looks for the arrows, dots and slider frame; mix any skin with any preset
* Text entrance animations — fade, fade up/down, slide from left/right or zoom, staggered across the sub heading, heading, text and button
* Device-specific settings — different slides-per-view, gap, height, arrows, dots and content visibility for tablet and mobile
* Slide or fade transitions, autoplay with a pause control
* Multiple slides per view (carousel) with adjustable gap
* Thumbnail navigation and Ken Burns zoom
* Overlay (solid or gradient), image fit, focus point and accent colour

**Performance**

* No jQuery on the front end
* Conditional asset loading — nothing loads on pages without a slider
* Lazy-loaded images, with the first slide eager and high-priority for a better LCP
* Sliders initialise only when scrolled into view
* RTL ready

**Accessibility**

* Full keyboard navigation and screen-reader labels
* Play/pause button for autoplay
* Respects the reduced-motion setting

**For developers**

* Filters: `general_slider_settings`, `general_slider_slides`, `general_slider_config`, `general_slider_html`, `general_slider_presets`, `general_slider_skins`, `general_slider_dynamic_query_args` and `general_slider_dynamic_slide`
* Register your own design preset or navigation skin, or reshape a dynamic slider's query and slides

**Demos & migration**

* Demo Library — import ready-made sliders, images included, in one click
* Export any slider as a portable demo package (.zip)
* JSON import / export, plus global default settings for new sliders

== Installation ==

1. Upload the plugin through Plugins > Add New, or upload the ZIP via Plugins > Add New > Upload.
2. Activate it through the Plugins menu.
3. Go to General Slider > Add New to create your first slider (or General Slider > Settings to import a demo).
4. Add the "General Slider" block to any page and choose your slider, or use the shortcode shown on the slider edit screen.

== Frequently Asked Questions ==

= How do I display a slider? =

Add the "General Slider" block to any page and pick your slider, or paste the shortcode `[general_slider id="123"]` (the exact shortcode is shown on each slider's edit screen).

= Why is my slider not showing? =

Check these in order: (1) the slider has at least one slide and is **published**; (2) the block or shortcode actually points to that slider — the exact shortcode is shown on the slider's edit screen; (3) if your site uses page caching, clear the cache so the latest version loads. Still stuck? Ask in the support forum with your page link and we'll help.

= Do I need to write any code? =

No. Everything is done from the WordPress admin.

= Does it load jQuery? =

No. The front end uses the lightweight, dependency-free Splide engine.

= Will it slow down my site? =

No. There is no jQuery and no external libraries; CSS/JS load only on pages that actually contain a slider, images are lazy-loaded (the first slide is prioritised for a better LCP), and sliders initialise only when scrolled into view.

= Does it work with caching plugins? =

Yes. General Slider works smoothly alongside caching and performance plugins. If you edit a slider and the change doesn't appear on the front end right away, just clear your site's cache to see the latest version.

= Can I change the look of the arrows and dots? =

Yes — pick a **navigation & frame skin** in the slider's settings box, or set a site-wide default under **General Slider → Settings**. Skins change the chrome (arrows, dots, frame) and work with every design preset: Classic, Soft (floating card), Stories and Stories Progress (segmented top lines — Progress fills over the autoplay interval), Numbers (editorial 01 02 03), Vertical (right-edge dots), Corner (square corner arrows), Neon (accent glow), Minimal, Pill, Outline, Retro, Glass and Dark. Developers can register their own via the `general_slider_skins` filter.

= How can I style the slider? =

Three layers, no code needed: pick a **design preset** (the slide layout), a **navigation & frame skin** (the chrome) and your **accent colour** — all in the slider's settings box, with site-wide defaults under **General Slider → Settings**. Overlay darkness/style, height, image fit and focus are per-slider too. For anything beyond that, each slider has its own **Custom CSS** box.

= Does it work with Elementor and page builders? =

Yes. There is a dedicated "General Slider" Elementor widget, and the shortcode works in any page builder or the classic editor.

= Does it work with my theme? =

Yes. It works with both block (FSE) themes and classic themes. Slide text inherits your theme's styling, and you can set an accent colour or add custom CSS per slider.

= Is it responsive and mobile friendly? =

Yes. Sliders are fully responsive, and multi-slide carousels automatically reduce the number of slides on tablets and phones. You can also set device-specific values: open the slider's "Responsive" settings to give tablet and mobile their own slides-per-view, gap, height, arrows, dots, or hide the slide text on small screens.

= How do I change the slider height? =

Each slider has a "Slide height (px)" setting in its settings box — set the height you want. You can also give tablet and mobile their own heights in the "Responsive" settings, so the slider fits every screen.

= Can I change the slide transition speed? =

Yes. In the slider's settings box, set "Transition speed (ms)" to control how quickly one slide changes into the next — lower is snappier, higher is more gradual. This is separate from "Autoplay speed", which is how long each slide stays before advancing.

= Can I build a slider from my posts or products automatically? =

Yes. In the Slides box, switch "Slides source" to **Pull slides from posts (dynamic)** and choose a content type — posts, pages or, if WooCommerce is active, products. The slider builds itself from your latest content (featured image, title, excerpt and link) and refreshes automatically when you publish or edit content. Every preset, skin and effect works with dynamic sliders too.

= Can I animate the slide text? =

Yes. In "Slider settings", pick a **Text animation** — fade, fade up or down, slide from the left or right, or zoom. The sub heading, heading, text and button enter one after another. Animations are pure CSS and respect the "reduced motion" accessibility setting.

= How do I show more than one slide at a time? =

Open the slider, and in "Slider settings" set "Slides per view" to 2 or more to create a carousel (great for logos, products or testimonials).

= Can I use a video background? =

Yes. In a slide's "Background video" field, paste a YouTube or Vimeo link, or a self-hosted MP4/WebM URL. The video plays muted and looped behind the slide content.

= Is it accessible? =

Yes. Sliders support keyboard navigation, screen-reader labels, a play/pause button for autoplay, and respect the "reduced motion" setting.

= Can I move sliders between sites? =

Yes. Use the JSON import / export tools on the General Slider > Settings screen, or export a slider as a demo package (.zip) and import it on another site.

= Can I create and duplicate multiple sliders? =

Yes. Build as many reusable sliders as you like, each with its own slides and settings, and use the Duplicate action to copy an existing slider as a starting point.

= Can I use more than one slider on the same page? =

Yes. Add as many sliders as you need to a single page — each one runs independently.

= Does it support right-to-left (RTL) languages? =

Yes. Sliders are RTL ready and follow your site's text direction automatically.

= Is General Slider translation ready? =

Yes. Every string is translatable and the plugin ships with a .pot template in the /languages folder.

= Does it work with Full Site Editing (FSE) and block themes? =

Yes. The General Slider block works in the block editor, the Site Editor and template parts, as well as in classic themes.

= Can developers customise the output? =

Yes. The plugin provides filters: `general_slider_settings` (a slider's resolved settings), `general_slider_slides` (the slides before rendering), `general_slider_config` (the Splide JS options), `general_slider_html` (the final markup), `general_slider_presets` (register your own design preset) and `general_slider_skins` (register your own navigation & frame skin). [Full hook reference with code examples](https://github.com/devmonowar/general-slider/blob/main/docs/hooks.md).

= Where do the demos come from? =

The Demo Library loads ready-made sliders from an online library so new demos can be added without updating the plugin. It only connects when you open the Demo Library screen or import a demo. See "External services" below.

You can also [browse the demos online](https://devmonowar.github.io/wp-plugin-demo-library/general-slider/) before installing.

= Is this plugin actively maintained? =

Yes. General Slider is actively developed, with regular feature updates and new demos. See the Changelog for the latest releases, and the [GitHub repository](https://github.com/devmonowar/general-slider) for ongoing development.

== External services ==

This plugin includes an optional **Demo Library** that loads ready-made sliders from a remote service hosted on GitHub Pages: [devmonowar.github.io/wp-plugin-demo-library](https://devmonowar.github.io/wp-plugin-demo-library/)

It connects to this service only when you:

* open the **Demo Library** screen — to download the list of demos and show their preview images; and
* click **Import Demo** — to download that demo's data and images into your site's Media Library.

These are plain, read-only requests for files. No personal data is collected or sent, and no request is made unless you use the Demo Library. The service is provided by GitHub Pages (GitHub, Inc.) — [terms of service](https://docs.github.com/site-policy/github-terms/github-terms-of-service) — [privacy statement](https://docs.github.com/site-policy/privacy-policies/github-privacy-statement).

== Screenshots ==

1. A full-width hero slider on the front end.
2. Cinematic full-bleed slides with overlay text and a call-to-action button.
3. Smooth autoplay with fade and slide transitions (animated).
4. Demo Library — import a ready-made slider in one click, images included.
5. Reusable sliders, each with a click-to-copy shortcode.
6. Per-slider and global settings: presets, transitions, overlay, image fit and accent colour.
7. Navigation & frame skins — the same slider wearing Soft, Stories, Numbers, Corner, Neon and Retro.
8. Skins in action — cycling through the navigation & frame skins (animated).
9. Thumbnail navigation — a clickable thumbnail strip under the slider.
10. Background video slides — muted, looped video behind your content (animated).

== Changelog ==

= 2.3.10 =
* Fixed: the author link on the Plugins screen led back to this plugin's own directory page. It now opens devmonowar.github.io.
* Added: a link to the full guide in the plugin description.

= 2.3.9 =
* Tested up to WordPress 7.0.4.

= 2.3.8 =
* New: a "Transition speed" control — set how quickly one slide changes into the next, separately from the autoplay wait time.
* Improved: tidier layout for the tablet and mobile options on the Settings screen.

= 2.3.7 =
* New: dynamic sliders — build a slider automatically from your posts, pages or WooCommerce products. Switch "Slides source" to Dynamic, pick a content type, and slides are generated from the featured image, title, excerpt and link, refreshing as you publish. Every preset, skin and effect still applies. Developers can reshape it with the `general_slider_dynamic_query_args` and `general_slider_dynamic_slide` filters.
* New: text animation presets — fade, fade up, fade down, slide from left, slide from right or zoom, staggered across the sub heading, heading, text and button. Pure CSS, and they respect reduced-motion.
* New: device-specific settings — give tablet and mobile their own slides-per-view, gap, height, arrows, dots, or hide the slide text on small screens.
* New: "Start from a demo" — creating a new slider now invites you to import a ready-made demo first.
* New: a gentle, dismissible reminder to leave a review, shown only on the plugin's own screens after a couple of weeks of use.
* Improved: front-end and admin CSS/JS now ship minified (the originals load when SCRIPT_DEBUG is on).
* Improved: text entrance animations now replay reliably on looping sliders.

= 2.3.6 =
* New: navigation & frame skins — Classic, Soft, Stories, Stories Progress, Numbers, Vertical, Corner, Neon, Minimal, Pill, Outline, Retro, Glass and Dark looks for the arrows, dots and slider frame. Pick one per slider or set a site-wide default; every skin works with every design preset. Developers can add their own via the `general_slider_skins` filter.
* New: the Stories Progress skin fills the current segment over the slide's autoplay time and pauses with it on hover.
* Improved: CSS/JS cache-busting now uses file modification times, so style/script updates always reach visitors immediately.
* Improved: sliders now initialise inside the Elementor editor preview.

= 2.3.5 =
* Documentation: clearer description with "Why General Slider?", "Perfect for" and grouped features, plus new FAQs (RTL, translation, Full Site Editing, duplicating sliders and using multiple sliders per page). No functional changes.

= 2.3.4 =
* Improved: smoother, more reliable one-click "Export Slider" downloads.
* New: a "Refresh" button on the Demo Library fetches the latest demos right away, instead of waiting for the cache to expire.
* New: imported demo images now get descriptive alt text automatically — better image SEO and accessibility. "Export Slider" includes each image's alt text in the package too.

= 2.3.3 =
* New: a starter demo ships with the plugin and is created automatically on first activation, so a fresh install isn't empty.
* New: import a demo package (.zip) directly from the Demo Library screen.
* New: click any slider shortcode to copy it.
* Improvement: the empty sliders list now offers "Create your first slider" and "Browse Demo Library".
* Improvement: slider buttons keep their accent colour and white text on any theme.
* Fix: the Button URL and whole-slide link fields now accept "#", relative URLs and anchors.

= 2.3.2 =
* New: Demo Library — browse a library of ready-made sliders and import one (with its images) in a single click. New demos are added online, so they appear without updating the plugin.
* New: Demo Export — turn any slider into a portable demo package from the sliders list.

= 2.3.1 =
* Maintenance: internal code-quality and coding-standards improvements (no functional changes).
* Improved: a precise uninstall that cleans up only the plugin's own data.

= 2.3.0 =
* New: developer filters — general_slider_settings, general_slider_slides, general_slider_config, general_slider_html and general_slider_presets.
* Performance: sliders now initialise only when they scroll near the viewport, so below-the-fold sliders no longer run JavaScript on page load.

= 2.2.0 =
* New: background video slides — self-hosted MP4/WebM (with the slide image as a poster), YouTube or Vimeo.
* New: gradient overlay style (in addition to the solid overlay).

= 2.1.0 =
* New: Elementor widget.
* New: two more presets — Testimonial and Fullscreen.
* New: whole-slide clickable link (with open-in-new-tab).
* New: multiple slides per view (carousel) with adjustable gap.
* New: thumbnail navigation.
* New: Ken Burns zoom and text entrance animations.
* New: accent colour control and a play/pause button for autoplay (accessibility).
* New: slider categories, duplicate action, custom CSS per slider, and JSON import / export.
* Performance: first slide image now loads eagerly with high fetch priority (better LCP); RTL ready.

= 2.0.0 =
* A brand-new, modern General Slider.
* New: reusable slider post type with a native slide editor (no third-party libraries).
* New: Gutenberg block to embed sliders.
* New: three design presets (Hero, Split, Minimal).
* New: per-slider image fit, image focus, height and overlay controls.
* New: one-click demo slider importer.
* New: global default settings page.
* Front-end engine: Splide — no jQuery, accessible, lazy-loaded images.
