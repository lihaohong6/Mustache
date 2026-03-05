<?php

namespace MediaWiki\Extension\Mustache\Tests;

use MediaWiki\Extension\Mustache\MustacheValidationFormatter;
use MediaWiki\Extension\Mustache\MustacheValidator;
use MediaWikiIntegrationTestCase;

class MustacheValidationFormatterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testRawInterpolationBlocked() {
		$templates = [
			'Simple raw interpolation' => '<div>{{{content}}}</div>',
			'Raw in attribute' => '<div class="{{{cls}}}">text</div>',
			'Alternate syntax' => '<div>{{&a}}</div>',
			'Disallow renaming to bypass checks' => '<div>{{= <{ }>}}</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Raw interpolation should be blocked: $description");
			$errorString = implode( ' ', $errors );
			$this->assertStringContainsStringIgnoringCase( 'raw', $errorString, "Error should mention raw interpolation: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testScriptTagInterpolationBlocked() {
		$templates = [
			'Simple script interpolation' => '<script>var x = "{{value}}";</script>',
			'Script with escaped content' => '<script>alert("{{msg}}");</script>',
			'Multiple script interpolations' => '<script>a="{{x}}";b="{{y}}";</script>',
			'Script tag with src attribute and interpolation' => '<script src="{{url}}" /><script>x="{{y}}"</script>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Script interpolation should be blocked: $description");
			$errorString = implode( ' ', $errors );
			$this->assertStringContainsStringIgnoringCase( 'script', $errorString, "Error should mention script tag: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testStyleTagInterpolationBlocked() {
		$templates = [
			'Simple style interpolation' => '<style>body { color: {{color}}; }</style>',
			'Style with background' => '<style>.x { background: url({{url}}); }</style>',
			'Multiple style interpolations' => '<style>a:{{prop}};b:{{val}};</style>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Style interpolation should be blocked: $description");
			$errorString = implode( ' ', $errors );
			$this->assertStringContainsStringIgnoringCase( 'style', $errorString, "Error should mention style tag: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testDangerousAttributeInterpolationBlocked() {
		$templates = [
			'onclick attribute' => '<div onclick="{{action}}">text</div>',
			'onerror attribute' => '<img src="img.png" onerror="{{handler}}">',
			'onload attribute' => '<div onload="{{init}}">text</div>',
			'onmouseover' => '<div onmouseover="{{effect}}">hover</div>',
			'formaction' => '<form formaction="{{action}}"></form>',
			'interpolation in attribute name' => '<div on{{name}}="something">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Dangerous attribute should be blocked: $description");
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testSafeAttributesAllowed() {
		$templates = [
			'id attribute' => '<div id="{{id}}">text</div>',
			'class attribute' => '<div class="{{cls}}">text</div>',
			'data-* attribute' => '<div data-value="{{val}}">text</div>',
			'aria-* attribute' => '<div aria-label="{{label}}">text</div>',
			'multiple safe attributes' => '<div id="{{id}}" class="{{cls}}" data-x="{{x}}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Safe attribute should be allowed: $description");
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testStaticScriptsAndHandlersAllowed() {
		$templates = [
			'Static script tag' => '<script>alert("XSS");</script>',
			'Static onclick handler' => '<div onclick="alert(1)">click</div>',
			'Static style tag' => '<style>body { color: red; }</style>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Static content should be allowed: $description");
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testMultipleErrorsCollected() {
		$template = '<script>var x = "{{val}}";</script><style>.x{{y}}{}</style><div onclick="{{z}}">';
		$errors = MustacheValidator::validateTemplate( $template );

		$this->assertNotEmpty( $errors );
		$errorString = implode( ' ', $errors );
		$this->assertStringContainsStringIgnoringCase( 'script', $errorString );
		$this->assertStringContainsStringIgnoringCase( 'style', $errorString );
		$this->assertStringContainsStringIgnoringCase( 'onclick', $errorString );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testMalformedHtmlParsedCorrectly() {
		$templates = [
			'Unclosed tag with interpolation' => '<div>{{x}}',
			'Malformed script tag' => '<script>var x = "{{y}}";<div></script>',
			'Self-closing script' => '<script src="{{url}}"/>',
			'Nested quotes' => '<div onclick="{{x}}\'{{y}}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );

			if ( str_contains( $template, '{{x}}' ) && str_contains( $template, 'onclick' ) ) {
				$this->assertNotEmpty( $errors, "Should detect dangerous attribute: $description");
			} elseif ( str_contains( $template, '{{y}}' ) ) {
				$this->assertNotEmpty( $errors, "Should detect interpolation: $description");
			}
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testEscapedInterpolationAllowed() {
		$templates = [
			'Simple escaped interpolation' => '<div>{{content}}</div>',
			'Multiple escaped' => '<div>{{a}}{{b}}{{c}}</div>',
			'In attribute' => '<div class="{{cls}}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Escaped interpolation should be allowed: $description");
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testEmptyAndWhitespaceTemplates() {
		$templates = [
			'Empty string' => '',
			'Whitespace only' => '   ',
			'Only HTML tags' => '<div><p><span></span></p></div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Empty/whitespace templates should pass: $description");
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testSpecialCharactersInTemplates() {
		$templates = [
			'HTML entities' => '<div class="test&amp;class">{{content}}</div>',
			'UTF-8 characters' => '<div>你好{{world}}</div>',
			'Emoji in template' => '<div>{{emoji}}</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Special characters should not cause false positives: $description");
		}
	}
}
