<?php
/**
 * Unit tests for GeneralSlider\Data — the pure data/sanitising layer.
 *
 * @package General_Slider
 */

use GeneralSlider\Data;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GeneralSlider\Data
 */
class DataTest extends TestCase {

	/* ---------- sanitize_settings ---------- */

	public function test_defaults_applied_for_empty_input() {
		$s = Data::sanitize_settings( array() );
		$this->assertSame( 'hero', $s['preset'] );
		$this->assertSame( 'classic', $s['skin'] );
		$this->assertSame( 'slide', $s['transition'] );
		$this->assertFalse( $s['autoplay'] );
		$this->assertSame( 'none', $s['animation'] );
	}

	public function test_invalid_choices_fall_back() {
		$s = Data::sanitize_settings(
			array(
				'preset'        => 'bogus',
				'skin'          => 'nope',
				'transition'    => 'spin',
				'overlay_style' => 'rainbow',
				'fit'           => 'squish',
				'focus'         => 'nowhere',
			)
		);
		$this->assertSame( 'hero', $s['preset'] );
		$this->assertSame( 'classic', $s['skin'] );
		$this->assertSame( 'slide', $s['transition'] );
		$this->assertSame( 'solid', $s['overlay_style'] );
		$this->assertSame( 'cover', $s['fit'] );
		$this->assertSame( 'center', $s['focus'] );
	}

	public function test_numeric_values_are_clamped() {
		$s = Data::sanitize_settings(
			array(
				'speed'    => 100,     // below the 1000 floor.
				'overlay'  => 250,     // above the 100 ceiling.
				'height'   => 5,       // below the 120 floor.
				'per_page' => 99,      // above the 6 ceiling.
				'gap'      => 999,     // above the 100 ceiling.
			)
		);
		$this->assertSame( 1000, $s['speed'] );
		$this->assertSame( 100, $s['overlay'] );
		$this->assertSame( 120, $s['height'] );
		$this->assertSame( 6, $s['per_page'] );
		$this->assertSame( 100, $s['gap'] );
	}

	public function test_transition_speed_default_and_clamp() {
		$this->assertSame( 600, Data::sanitize_settings( array() )['transition_speed'] );
		$this->assertSame( 100, Data::sanitize_settings( array( 'transition_speed' => 10 ) )['transition_speed'] );
		$this->assertSame( 3000, Data::sanitize_settings( array( 'transition_speed' => 99999 ) )['transition_speed'] );
		$this->assertSame( 450, Data::sanitize_settings( array( 'transition_speed' => 450 ) )['transition_speed'] );
	}

	public function test_accent_colour_validated() {
		$this->assertSame( '#abcdef', Data::sanitize_settings( array( 'accent' => '#abcdef' ) )['accent'] );
		$this->assertSame( '#2196f3', Data::sanitize_settings( array( 'accent' => 'red' ) )['accent'] );
	}

	/* ---------- animation presets + backward compatibility ---------- */

	public function test_animation_preset_kept_and_derives_animate() {
		$s = Data::sanitize_settings( array( 'animation' => 'zoom' ) );
		$this->assertSame( 'zoom', $s['animation'] );
		$this->assertTrue( $s['animate'] );
	}

	public function test_invalid_animation_becomes_none() {
		$s = Data::sanitize_settings( array( 'animation' => 'boom' ) );
		$this->assertSame( 'none', $s['animation'] );
		$this->assertFalse( $s['animate'] );
	}

	public function test_legacy_animate_boolean_maps_to_fade_up() {
		$s = Data::sanitize_settings( array( 'animate' => '1' ) );
		$this->assertSame( 'fade-up', $s['animation'] );
	}

	public function test_resolve_animation_honours_legacy_and_explicit() {
		$this->assertSame( 'fade-up', Data::resolve_animation( array( 'animate' => true ) ) );
		$this->assertSame(
			'slide-left',
			Data::resolve_animation(
				array(
					'animate' => true,
					'animation' => 'slide-left',
				)
			)
		);
		$this->assertSame( 'none', Data::resolve_animation( array() ) );
		$this->assertSame( 'none', Data::resolve_animation( array( 'animation' => 'invalid' ) ) );
	}

	/* ---------- slides source ---------- */

	public function test_source_defaults_to_manual() {
		$src = Data::sanitize_source( array() );
		$this->assertSame( 'manual', $src['type'] );
		$this->assertSame( 'post', $src['post_type'] );
		$this->assertSame( 6, $src['count'] );
	}

	public function test_source_sanitises_and_clamps() {
		$src = Data::sanitize_source(
			array(
				'type'          => 'dynamic',
				'post_type'     => 'Product',
				'orderby'       => 'title',
				'order'         => 'asc',
				'count'         => 999,
				'excerpt_words' => 150,
				'show_excerpt'  => '1',
				'button_text'   => '  Read more  ',
			)
		);
		$this->assertSame( 'dynamic', $src['type'] );
		$this->assertSame( 'product', $src['post_type'] );
		$this->assertSame( 'title', $src['orderby'] );
		$this->assertSame( 'ASC', $src['order'] );
		$this->assertSame( 20, $src['count'] );
		$this->assertSame( 100, $src['excerpt_words'] );
		$this->assertTrue( $src['show_excerpt'] );
		$this->assertSame( 'Read more', $src['button_text'] );
	}

	public function test_source_invalid_orderby_falls_back() {
		$this->assertSame( 'date', Data::sanitize_source( array( 'orderby' => 'hacks' ) )['orderby'] );
	}

	/* ---------- responsive breakpoints ---------- */

	public function test_responsive_only_stores_touched_fields() {
		$s = Data::sanitize_settings(
			array(
				'responsive' => array(
					'mobile' => array(
						'per_page' => '2',
						'gap' => '',
						'hide_content' => '1',
						'arrows' => 'hide',
					),
				),
			)
		);
		$this->assertSame( 2, $s['responsive']['mobile']['per_page'] );
		$this->assertArrayNotHasKey( 'gap', $s['responsive']['mobile'] );
		$this->assertTrue( $s['responsive']['mobile']['hide_content'] );
		$this->assertFalse( $s['responsive']['mobile']['arrows'] );
		$this->assertSame( array(), $s['responsive']['tablet'] );
	}

	/* ---------- list integrity ---------- */

	public function test_skins_include_the_shipped_set() {
		$skins = Data::skins();
		foreach ( array( 'classic', 'soft', 'progress', 'numbers', 'neon', 'corner' ) as $key ) {
			$this->assertArrayHasKey( $key, $skins );
		}
	}

	public function test_animations_list_shape() {
		$anims = Data::animations();
		$this->assertArrayHasKey( 'none', $anims );
		$this->assertArrayHasKey( 'fade-up', $anims );
		$this->assertArrayHasKey( 'zoom', $anims );
	}
}
