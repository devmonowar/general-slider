<?php
/**
 * Unit tests for GeneralSlider\Tools::clean_css().
 *
 * @package General_Slider
 */

use GeneralSlider\Tools;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/Tools.php';

/**
 * @covers \GeneralSlider\Tools::clean_css
 */
class ToolsTest extends TestCase {

	public function test_keeps_modern_range_syntax() {
		$css = '@media (width < 768px){.gs-slide__title{font-size:22px}}';
		$this->assertSame( $css, Tools::clean_css( $css ) );
	}

	public function test_keeps_child_combinators() {
		$css = '#gs-slider-5 > .gs-slide__title{color:red}';
		$this->assertSame( $css, Tools::clean_css( $css ) );
	}

	public function test_neutralises_style_breakout() {
		$out = Tools::clean_css( '</style><script>alert(1)</script>' );
		$this->assertStringNotContainsString( '</', $out );
	}

	public function test_trims_and_passes_plain_css_through() {
		$this->assertSame( '.a{color:red}', Tools::clean_css( "  .a{color:red}\n" ) );
	}
}
