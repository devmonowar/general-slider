=== General Slider ===
Contributors: devmonowar
Tags: slider, carousel, image slider, slideshow, gutenberg
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create beautiful, reusable carousel sliders in minutes — lightweight, block-ready, and no coding required.

== Description ==

General Slider is a fast, modern slider for WordPress. Create a slider once, then place it on any page with the block or a shortcode. No page builder and no coding required.

Built on the lightweight Splide engine — no jQuery on the front end, accessible by default, and assets only load on pages that actually show a slider.

Use it as a hero slider, image carousel, testimonial slider, logo carousel, content slideshow or fullscreen banner. Works in the block editor, with a shortcode, and as an Elementor widget — on any block theme or classic theme.

= Features =

* Reusable sliders — build once, use anywhere
* Gutenberg block, shortcode and Elementor widget
* Five design presets — Hero, Split, Minimal, Testimonial, Fullscreen
* One-click demo slider so you can see how it works straight away
* Per-slide image or background video (self-hosted MP4/WebM, YouTube or Vimeo), sub heading, heading, text, button and a whole-slide link
* Multiple slides per view (carousel) with adjustable gap
* Thumbnail navigation
* Ken Burns zoom and text entrance animations
* Per-slider settings: autoplay (+ speed and pause button), loop, arrows, dots, slide/fade transition, height, overlay (solid or gradient), image fit, image focus and accent colour
* Custom CSS, categories, duplicate and JSON import / export
* Global default settings for new sliders
* Responsive, accessible (keyboard + screen reader, pause control), respects reduced-motion
* Performance friendly: no jQuery on the front end, lazy-loaded images (eager + high-priority first slide), conditional asset loading, RTL ready

== Installation ==

1. Upload the plugin through Plugins > Add New, or upload the ZIP via Plugins > Add New > Upload.
2. Activate it through the Plugins menu.
3. Go to General Slider > Add New to create your first slider (or General Slider > Settings to import a demo).
4. Add the "General Slider" block to any page and choose your slider, or use the shortcode shown on the slider edit screen.

== Frequently Asked Questions ==

= How do I display a slider? =

Add the "General Slider" block to any page and pick your slider, or paste the shortcode `[general_slider id="123"]` (the exact shortcode is shown on each slider's edit screen).

= Do I need to write any code? =

No. Everything is done from the WordPress admin.

= Does it load jQuery? =

No. The front end uses the lightweight, dependency-free Splide engine.

= Does it work with Elementor and page builders? =

Yes. There is a dedicated "General Slider" Elementor widget, and the shortcode works in any page builder or the classic editor.

= Does it work with my theme? =

Yes. It works with both block (FSE) themes and classic themes. Slide text inherits your theme's styling, and you can set an accent colour or add custom CSS per slider.

= Is it responsive and mobile friendly? =

Yes. Sliders are fully responsive, and multi-slide carousels automatically reduce the number of slides on tablets and phones.

= How do I show more than one slide at a time? =

Open the slider, and in "Slider settings" set "Slides per view" to 2 or more to create a carousel (great for logos, products or testimonials).

= Can I use a video background? =

Yes. In a slide's "Background video" field, paste a YouTube or Vimeo link, or a self-hosted MP4/WebM URL. The video plays muted and looped behind the slide content.

= Is it accessible? =

Yes. Sliders support keyboard navigation, screen-reader labels, a play/pause button for autoplay, and respect the "reduced motion" setting.

= Can I move sliders between sites? =

Yes. Use the JSON import / export tools on the General Slider > Settings screen.

= Can developers customise the output? =

Yes. The plugin provides filters: `general_slider_settings` (a slider's resolved settings), `general_slider_slides` (the slides before rendering), `general_slider_config` (the Splide JS options), `general_slider_html` (the final markup) and `general_slider_presets` (register your own design preset).

= Where do the demos come from? =

The Demo Library loads ready-made sliders from an online library so new demos can be added without updating the plugin. It only connects when you open the Demo Library screen or import a demo. See "External services" below.

== External services ==

This plugin includes an optional **Demo Library** that loads ready-made sliders from a remote service hosted on GitHub Pages: https://devmonowar.github.io/wp-plugin-demo-library/

It connects to this service only when you:

* open the **Demo Library** screen — to download the list of demos and show their preview images; and
* click **Import Demo** — to download that demo's data and images into your site's Media Library.

These are plain, read-only requests for files. No personal data is collected or sent, and no request is made unless you use the Demo Library. The service is provided by GitHub Pages (GitHub, Inc.) — terms of service: https://docs.github.com/site-policy/github-terms/github-terms-of-service — privacy statement: https://docs.github.com/site-policy/privacy-policies/github-privacy-statement

== Screenshots ==

1. A full-width hero slider on the front end.
2. Cinematic full-bleed slides with overlay text and a call-to-action button.
3. Smooth autoplay with fade and slide transitions (animated).
4. Demo Library — import a ready-made slider in one click, images included.
5. Reusable sliders, each with a click-to-copy shortcode.
6. Per-slider and global settings: presets, transitions, overlay, image fit and accent colour.

== Changelog ==

= 2.3.4 =
* Fix: "Export Slider" now downloads correctly — the auto-download link was being HTML-encoded and failed with an expired-link error.
* New: a "Refresh" button on the Demo Library fetches the latest demos right away, instead of waiting for the cache to expire.

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
* Fix: uninstall now removes only the plugin's own data instead of flushing the entire site object cache.

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
* Complete rewrite. Modern, object-oriented codebase.
* New: reusable slider post type with a native slide editor (no third-party libraries).
* New: Gutenberg block to embed sliders.
* New: three design presets (Hero, Split, Minimal).
* New: per-slider image fit, image focus, height and overlay controls.
* New: one-click demo slider importer.
* New: global default settings page.
* Switched the front-end engine to Splide — no jQuery, accessible, lazy-loaded images.
* Note: this is a ground-up rebuild and does not migrate data from the 1.x series.
