<?php
/**
 * Tests for converting strings with and without formatting tags.
 *
 * @package  WP SEO
 */
class WP_SEO_Format_Tests extends WP_UnitTestCase {

	/**
	 * String with no formatting tags (but some complex characters).
	 *
	 * @var string.
	 */
	var $string_no_tags = "Markup: Title With Special Characters #~`!@#$%^&amp;*()-_=+{}[]/;:'?,.&gt;";

	/**
	 * String with one of the default formatting tags.
	 *
	 * @var string.
	 */
	var $string_default_tag = 'Welcome | #site_name#';

	/**
	 * String with an unknown tag.
	 *
	 * @var string
	 */
	var $string_unknown_tag = 'All posts by #twitter_handle#';

	function test_empty_string() {
		$this->assertSame( '', WP_SEO()->format( '' ) );
	}

	function test_not_a_string() {
		$this->assertWPError( WP_SEO()->format( array() ) );
	}

	function test_string_no_tags() {
		$this->assertSame( $this->string_no_tags, WP_SEO()->format( $this->string_no_tags ) );
	}

	function test_default_tag() {
		$this->assertSame( 'Welcome | Test Blog', WP_SEO()->format( $this->string_default_tag ) );
	}

	function test_unknown_tag() {
		$this->assertSame( $this->string_unknown_tag, WP_SEO()->format( $this->string_unknown_tag ) );
	}

	function test_known_and_unknown_tags() {
		$combined = sprintf( '%s | %s', $this->string_default_tag, $this->string_unknown_tag );
		$this->assertSame( 'Welcome | Test Blog | '. $this->string_unknown_tag, WP_SEO()->format( $combined ) );
	}

}

