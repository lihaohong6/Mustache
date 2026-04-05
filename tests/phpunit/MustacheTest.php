<?php

namespace MediaWiki\Extension\Mustache\Tests;

use MediaWiki\Extension\Mustache\MustacheRenderer;
use MediaWikiIntegrationTestCase;

class MustacheTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testSafeHrefPreserved() {
		$safeUrls = [
			'<a href="https://example.com">Link</a>' => '<a href="https://example.com">Link</a>',
			'<a href="http://example.com">Link</a>' => '<a href="http://example.com">Link</a>',
			'<a href="/path/to/page">Link</a>' => '<a href="/path/to/page">Link</a>',
			'<a href="/absolute/path">Link</a>' => '<a href="/absolute/path">Link</a>',
			'<img src="https://example.com/image.png">' => '<img src="https://example.com/image.png" />',
			'<img src="http://example.com/image.jpg">' => '<img src="http://example.com/image.jpg" />',
			'<img src="/images/logo.png">' => '<img src="/images/logo.png" />',
		];

		foreach ( $safeUrls as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Safe URL should be preserved: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testSafeStylePreserved() {
		$safeStyles = [
			'<div style="color: red;">Text</div>' => '<div style="color: red;">Text</div>',
		];

		foreach ( $safeStyles as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Safe style should be preserved: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testDangerousStyleRemoved() {
		$dangerousStyles = [
			'<div style="color: expression(alert(1))">Text</div>' => '<div style="/* insecure input */">Text</div>',
			'<div style="background: url(javascript:alert(1))">Text</div>' => '<div style="/* insecure input */">Text</div>',
			'<div style="xss: expression(alert(1))">Text</div>' => '<div style="/* insecure input */">Text</div>',
			'<div style="-moz-binding: url(xss.xml)">Text</div>' => '<div style="/* insecure input */">Text</div>',
			'<div style="behavior: url(xss.htc)">Text</div>' => '<div style="/* insecure input */">Text</div>',
		];

		foreach ( $dangerousStyles as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Dangerous style should be removed: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testAttributeCaseInsensitivity() {
		$cases = [
			'<a HREF="https://safe.com">Link</a>' => '<a href="https://safe.com">Link</a>',
			'<img SRC="/safe.png">' => '<img src="/safe.png" />',
			'<div STYLE="expression(alert(1))">Text</div>' => '<div style="/* insecure input */">Text</div>',
		];

		foreach ( $cases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Attribute case should be handled correctly: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testSpecialCharactersInUrls() {
		$specialCases = [
			'<a href="/path?param=value&amp;other=test">Link</a>' => '<a href="/path?param=value&amp;other=test">Link</a>',
			'<a href="https://example.com/path#anchor">Link</a>' => '<a href="https://example.com/path#anchor">Link</a>',
			'<a href="https://example.com/path?query=value">Link</a>' => '<a href="https://example.com/path?query=value">Link</a>',
		];

		foreach ( $specialCases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "URL with special characters should be handled: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testNonUrlAttributesUnaffected() {
		$input = '<div id="test" class="container" data-value="123" aria-label="Test">' .
			'Content' .
			'</div>';
		$expected = $input;

		$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
		$this->assertSame( $expected, $result, "Non-url attributes should be unaffected" );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testWhitespaceInAttributes() {
		$whitespaceCases = [
			'<a href=" https://safe.com ">Link</a>' => '<a href=" https://safe.com ">Link</a>',
			'<div style=" color: red; ">Text</div>' => '<div style=" color: red; ">Text</div>',
		];

		foreach ( $whitespaceCases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Whitespace in attributes should be handled: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testCssSelectorFilter() {
		$cases = [
			'safe identifier'  => [ '{{ id|css-selector }}', [ 'id' => 'my-class_1' ], 'my-class_1' ],
			'strips spaces'    => [ '{{ id|css-selector }}', [ 'id' => 'foo bar' ], 'foobar' ],
			'strips html tags' => [ '{{ id|css-selector }}', [ 'id' => '<script>' ], 'script' ],
		];
		foreach ( $cases as $desc => [ $template, $data, $expected ] ) {
			$result = MustacheRenderer::render( $template, $data );
			$this->assertStringContainsString( $expected, $result, "css-selector: $desc" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testCssValueFilter() {
		// Safe CSS value passes through
		$result = MustacheRenderer::render(
			'<div style="color: {{ val|css-value }}">x</div>',
			[ 'val' => 'red' ]
		);
		$this->assertStringContainsString( 'color: red', $result );

		// Dangerous CSS expression is sanitized
		$result = MustacheRenderer::render(
			'<div style="{{ val|css-value }}">x</div>',
			[ 'val' => 'expression(alert(1))' ]
		);
		$this->assertStringNotContainsString( 'expression', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testJsStringFilter() {
		$cases = [
			'plain string'    => [ '{{ s|js-string }}', [ 's' => 'hello world' ], "'hello world'" ],
			'escapes quotes'  => [ '{{ s|js-string }}', [ 's' => 'say "hi"' ], "'say \\u0022hi\\u0022'" ],
			'escapes html lt' => [ '{{ s|js-string }}', [ 's' => '<script>' ], "'\\u003Cscript\\u003E'" ],
			'escapes amp'     => [ '{{ s|js-string }}', [ 's' => 'a&b' ], "'a\\u0026b'" ],
			'escapes newline' => [ '{{ s|js-string }}', [ 's' => "line1\nline2" ], "'line1\\nline2'" ],
		];
		foreach ( $cases as $desc => [ $template, $data, $expected ] ) {
			$result = MustacheRenderer::render( $template, $data );
			$this->assertStringContainsString( $expected, $result, "js-string: $desc" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testJsIdentifierFilter() {
		$cases = [
			'valid identifier' => [ '{{ v|js-identifier }}', [ 'v' => 'myVar_1' ], 'myVar_1' ],
			'strips spaces'    => [ '{{ v|js-identifier }}', [ 'v' => 'my var' ], 'myvar' ],
			'strips html'      => [ '{{ v|js-identifier }}', [ 'v' => '<foo>' ], 'foo' ],
			'keeps dollar'     => [ '{{ v|js-identifier }}', [ 'v' => '$var' ], '$var' ],
		];
		foreach ( $cases as $desc => [ $template, $data, $expected ] ) {
			$result = MustacheRenderer::render( $template, $data );
			$this->assertStringContainsString( $expected, $result, "js-identifier: $desc" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testUrlFilter() {
		// Safe URLs pass through
		$result = MustacheRenderer::render( '{{ u|url }}', [ 'u' => 'https://example.com' ] );
		$this->assertStringContainsString( 'https://example.com', $result );

		// Dangerous schemes are stripped to empty string
		$result = MustacheRenderer::render( '{{ u|url }}', [ 'u' => 'javascript:alert(1)' ] );
		$this->assertStringNotContainsString( 'javascript', $result );

		$result = MustacheRenderer::render( '{{ u|url }}', [ 'u' => 'data:text/html,foo' ] );
		$this->assertStringNotContainsString( 'data:', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testAttributeFilter() {
		$result = MustacheRenderer::render(
			'<div class="{{ cls|attribute }}">text</div>',
			[ 'cls' => 'my-class "test"' ]
		);
		$this->assertStringContainsString( 'my-class &quot;test&quot;', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer
	 */
	public function testFilterOutputNotDoubleEscaped() {
		// attribute filter produces &quot; — must not be re-encoded to &amp;quot;
		$result = MustacheRenderer::render(
			'<div class="{{ cls|attribute }}">text</div>',
			[ 'cls' => 'a"b' ]
		);
		$this->assertStringContainsString( '&quot;', $result );
		$this->assertStringNotContainsString( '&amp;quot;', $result );

		// js-string filter wraps in single quotes — must not become &#039;
		$result = MustacheRenderer::render( '{{ s|js-string }}', [ 's' => 'hello' ] );
		$this->assertStringContainsString( "'hello'", $result );
		$this->assertStringNotContainsString( '&#039;', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheFilters
	 */
	public function testUnicodeAndSpecialCharacters() {
		$unicodeCases = [
			'<a href="https://example.com/中文">Link</a>' => '<a href="https://example.com/中文">Link</a>',
			'<div style="font-family: Arial;">Text</div>' => '<div style="font-family: Arial;">Text</div>',
		];

		foreach ( $unicodeCases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Unicode and special characters should be handled: $input" );
		}
	}
}
