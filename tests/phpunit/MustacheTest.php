<?php

namespace MediaWiki\Extension\Mustache\Tests;

use MediaWiki\Extension\Mustache\MustacheDataParser;
use MediaWiki\Extension\Mustache\MustacheRenderer;
use MediaWiki\Extension\Mustache\MustacheValidator;
use MediaWikiIntegrationTestCase;

class MustacheTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidator::validateTemplate
	 */
	public function testAllowedTemplates() {
		$allowedTemplates = [
			# href is sanitized later
			'Safe attributes (id, class, href)' => '<div id="{{id}}" class="{{cls}}" href="{{href}}">{{content}}</div>',
			'data-* attributes' => '<div data-value="{{value}}">{{content}}</div>',
			'Static event handlers' => '<img src="image.png" onerror="alert(\'XSS\')">',
			'Static script' => '<script>alert(\'XSS\');</script>',
		];

		foreach ( $allowedTemplates as $description => $templateContent ) {
			$errors = MustacheValidator::validateTemplate( $templateContent );
			$this->assertEmpty( $errors, "Template should be allowed: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidator::validateTemplate
	 */
	public function testRejectedDangerousAttributes() {
		$rejectedTemplates = [
			'onclick with interpolation' => '<div onclick="{{action}}">{{content}}</div>',
			'onerror with interpolation' => '<img src="image.png" onerror="alert({{message}})">',
		];

		foreach ( $rejectedTemplates as $description => $templateContent ) {
			$errors = MustacheValidator::validateTemplate( $templateContent );
			$this->assertNotEmpty( $errors, "Template should be rejected: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidator::validateTemplate
	 */
	public function testRejectedInterpolationPatterns() {
		$rejectedTemplates = [
			'Raw interpolation' => '<div>{{{content}}}</div>',
			'Script interpolation' => '<script>const x = "{{value}}";</script>',
		];

		foreach ( $rejectedTemplates as $description => $testCase ) {
			$errors = MustacheValidator::validateTemplate( $testCase );
			$this->assertNotEmpty( $errors, "Template should be rejected: $description" );
		}
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
	public function testDangerousHrefRemoved() {
		$dangerousUrls = [
			'<a href="javascript:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a href="javascript:void(0)">Link</a>' => '<a href="">Link</a>',
			'<a href="javascript:document.cookie">Link</a>' => '<a href="">Link</a>',
			'<a href="data:text/html,<script>alert(1)</script>">Link</a>' => '<a href="">Link</a>',
			'<a href="vbscript:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a href="file:///etc/passwd">Link</a>' => '<a href="">Link</a>',
			'<img src="javascript:alert(1)">' => '<img src="" />',
			'<img src="data:text/html,<script>alert(1)</script>">' => '<img src="" />',
		];

		foreach ( $dangerousUrls as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Dangerous URL should be removed: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testMixedCaseDangerousProtocolsRemoved() {
		$caseVariations = [
			'<a href="JavaScript:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a href="JAVASCRIPT:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a href="JaVaScRiPt:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a href="Data:text/html,<script>alert(1)</script>">Link</a>' => '<a href="">Link</a>',
			'<a href="VBScript:alert(1)">Link</a>' => '<a href="">Link</a>',
		];

		foreach ( $caseVariations as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Case variant of dangerous protocol should be removed: $input" );
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
	public function testMultipleAttributesSanitized() {
		$input = '<a href="javascript:alert(1)" class="test" style="expression(alert(2))">Link</a>';
		$result = MustacheRenderer::sanitizeRenderedTemplate( $input );

		$this->assertStringContainsString( 'class="test"', $result, "Safe class attribute should be preserved" );
		$this->assertStringContainsString( 'href=""', $result, "Dangerous href should be removed" );
		$this->assertStringContainsString( 'style="/* insecure input */"', $result, "Dangerous style should be removed" );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testAttributeCaseInsensitivity() {
		$cases = [
			'<a HREF="javascript:alert(1)">Link</a>' => '<a href="">Link</a>',
			'<a HREF="https://safe.com">Link</a>' => '<a href="https://safe.com">Link</a>',
			'<img SRC="javascript:alert(1)">' => '<img src="" />',
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
	public function testMalformedHtmlHandled() {
		$malformedCases = [
			'<a href="javascript:alert(1)">Link' => '<a href="">Link</a>',
			'<div href="https://test.com">' => '<div href="https://test.com"></div>',
		];

		foreach ( $malformedCases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Malformed HTML should be handled: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
	 */
	public function testWhitespaceInAttributes() {
		$whitespaceCases = [
			'<a href=" javascript:alert(1) ">Link</a>' => '<a href="">Link</a>',
			'<a href=" https://safe.com ">Link</a>' => '<a href=" https://safe.com ">Link</a>',
			'<div style=" color: red; ">Text</div>' => '<div style=" color: red; ">Text</div>',
		];

		foreach ( $whitespaceCases as $input => $expected ) {
			$result = MustacheRenderer::sanitizeRenderedTemplate( $input );
			$this->assertSame( $expected, $result, "Whitespace in attributes should be handled: $input" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheRenderer::sanitizeRenderedTemplate
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
