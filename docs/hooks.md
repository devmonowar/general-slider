# General Slider — Developer Hooks

General Slider is built to be extended. Every slider's settings, slides, configuration
and markup pass through filters before they reach the page, so you can customise
behaviour from your theme's `functions.php` or a small plugin — no core edits required.

All filters are prefixed `general_slider_`.

| Filter | Use it to |
|--------|-----------|
| [`general_slider_settings`](#general_slider_settings) | Change a slider's settings before render |
| [`general_slider_slides`](#general_slider_slides) | Add, remove or modify slides |
| [`general_slider_config`](#general_slider_config) | Tweak the front-end (Splide) behaviour |
| [`general_slider_html`](#general_slider_html) | Alter the final rendered HTML |
| [`general_slider_presets`](#general_slider_presets) | Register your own design preset |
| [`general_slider_skins`](#general_slider_skins) | Register your own navigation & frame skin |
| [`general_slider_dynamic_query_args`](#general_slider_dynamic_query_args) | Change the `WP_Query` for a dynamic slider |
| [`general_slider_dynamic_slide`](#general_slider_dynamic_slide) | Map a post onto a dynamic slide |
| [`general_slider_demo_library_url`](#general_slider_demo_library_url) | Point the Demo Library at a custom manifest |

---

## `general_slider_settings`

Filter a slider's resolved settings just before it renders.

**Parameters**

- `array $settings` — Resolved settings: `preset`, `transition`, `autoplay`, `speed`, `loop`, `arrows`, `dots`, `overlay`, `overlay_style`, `height`, `focus`, `fit`, `per_page`, `gap`, `accent`, `ken_burns`, `animate`, `thumbnails`.
- `int   $post_id` — The slider (post) ID.

```php
add_filter( 'general_slider_settings', function ( $settings, $post_id ) {
	// Turn autoplay off for one specific slider.
	if ( 42 === $post_id ) {
		$settings['autoplay'] = false;
	}
	return $settings;
}, 10, 2 );
```

## `general_slider_slides`

Filter the normalised slides before they are rendered.

**Parameters**

- `array $slides` — List of slide arrays. Each may contain: `image_id`, `video`, `sub_heading`, `heading`, `text`, `btn_text`, `btn_url`, `link`, `new_tab`.
- `int   $post_id` — The slider ID.

```php
add_filter( 'general_slider_slides', function ( $slides, $post_id ) {
	// Append a promo slide to every slider.
	$slides[] = array(
		'heading'  => 'Summer sale',
		'text'     => 'Up to 50% off, this week only.',
		'btn_text' => 'Shop now',
		'btn_url'  => '/sale',
	);
	return $slides;
}, 10, 2 );
```

## `general_slider_config`

Filter the [Splide](https://splidejs.com/) options passed to the front-end script.

**Parameters**

- `array $config` — Splide options: `type`, `autoplay`, `interval`, `arrows`, `pagination`, `perPage`, `gap`, `direction`.
- `int   $post_id` — The slider ID.

```php
add_filter( 'general_slider_config', function ( $config, $post_id ) {
	// Pause autoplay on hover and speed up the transition.
	$config['pauseOnHover'] = true;
	$config['speed']        = 600;
	return $config;
}, 10, 2 );
```

See the [Splide options reference](https://splidejs.com/guides/options/) for every available key.

## `general_slider_html`

Filter the final HTML markup for a slider.

**Parameters**

- `string $html`     — The rendered slider markup.
- `int    $post_id`  — The slider ID.
- `array  $settings` — The resolved settings used to render it.

```php
add_filter( 'general_slider_html', function ( $html, $post_id, $settings ) {
	// Wrap every slider in a custom container.
	return '<div class="my-slider-wrap">' . $html . '</div>';
}, 10, 3 );
```

## `general_slider_presets`

Register your own design preset. Add a `key => label` pair, then ship a matching
`.gs-preset-{key}` stylesheet so the front end can style it.

**Parameters**

- `array $presets` — `preset_key => label` pairs. Built-ins: `hero`, `split`, `minimal`, `testimonial`, `fullscreen`.

```php
add_filter( 'general_slider_presets', function ( $presets ) {
	$presets['spotlight'] = 'Spotlight';
	return $presets;
} );

// Then enqueue a stylesheet that targets `.gs-preset-spotlight`.
```

## `general_slider_skins`

Register your own navigation & frame skin. Skins style the slider's chrome — the
arrows, dots and frame — and work with every design preset. Add a `key => label`
pair, then ship a matching `.gs-skin-{key}` stylesheet.

**Parameters**

- `array $skins` — `skin_key => label` pairs. Built-ins include `classic`, `soft`, `stories`, `progress`, `numbers`, `vertical`, `corner`, `neon`, `minimal`, `pill`, `outline`, `retro`, `glass` and `dark`.

```php
add_filter( 'general_slider_skins', function ( $skins ) {
	$skins['brutal'] = 'Brutal';
	return $skins;
} );

// Then enqueue a stylesheet that targets `.gs-skin-brutal`
// (e.g. `.gs-skin-brutal .splide__arrow { … }`).
```

## `general_slider_dynamic_query_args`

Reshape the `WP_Query` a **dynamic** slider runs to gather its slides — filter by
meta, add a `tax_query`, change ordering, and so on. Applies only to sliders whose
"Slides source" is set to Dynamic.

**Parameters**

- `array $args` — `WP_Query` arguments (post type, status, `posts_per_page`, `orderby`, `order`, optional `tax_query`).
- `int   $post_id` — The slider ID.
- `array $source` — The slider's resolved source config.

```php
add_filter( 'general_slider_dynamic_query_args', function ( $args, $post_id, $source ) {
	// Only show posts flagged as featured.
	$args['meta_key']   = 'is_featured';
	$args['meta_value'] = '1';
	return $args;
}, 10, 3 );
```

## `general_slider_dynamic_slide`

Change how a single post is mapped onto a dynamic slide — override the heading,
text, button, image or whole-slide link before the slide is rendered.

**Parameters**

- `array    $slide` — The slide (`image_id`, `sub_heading`, `heading`, `text`, `btn_text`, `btn_url`, `link`, …).
- `WP_Post  $post` — The source post.
- `array    $source` — The slider's resolved source config.

```php
add_filter( 'general_slider_dynamic_slide', function ( $slide, $post, $source ) {
	// Use the category name as the sub-heading.
	$cats = get_the_category( $post->ID );
	if ( $cats ) {
		$slide['sub_heading'] = $cats[0]->name;
	}
	return $slide;
}, 10, 3 );
```

## `general_slider_demo_library_url`

Override the URL of the remote Demo Library manifest — useful for a staging or a
private demo set. You can also define the `GS_DEMO_LIBRARY_URL` constant in `wp-config.php`.

**Parameters**

- `string $url` — The manifest URL.

```php
add_filter( 'general_slider_demo_library_url', function ( $url ) {
	return 'https://example.com/my-demos/demo-library.json';
} );
```

---

Found a bug, or want a new hook? Open an issue or pull request on
[GitHub](https://github.com/devmonowar/general-slider).
