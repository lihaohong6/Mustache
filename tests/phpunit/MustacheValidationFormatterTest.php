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
	public function testScriptTagWithJsFiltersAllowed() {
		$templates = [
			'js-string filter'          => '<script>var x = "{{ value|js-string }}";</script>',
			'js-number filter'          => '<script>var n = {{ count|js-number }};</script>',
			'js-identifier filter'      => '<script>var {{ name|js-identifier }} = 1;</script>',
			'js-string in function'     => '<script>function f() { alert("{{ msg|js-string }}"); }</script>',
			'section inside script'     => '<script>{{#items}}var {{ name|js-identifier }} = 1;{{/items}}</script>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Script tag with JS filter should be allowed: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testStyleTagWithCssFiltersAllowed() {
		$templates = [
			'css-id for class selector'    => '<style>.{{ cls|css-id }} { color: red; }</style>',
			'css-id for id selector'       => '<style>#{{ id|css-id }} { font-size: 16px; }</style>',
			'css-value for property value' => '<style>.foo { color: {{ color|css-value }}; }</style>',
			'section inside style'         => '<style>{{#themes}}.{{ name|css-id }} { color: red; }{{/themes}}</style>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertEmpty( $errors, "Style tag with CSS filter should be allowed: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testUnknownFilterBlocked() {
		$templates = [
			'Unknown filter in attribute' => '<div class="{{ cls|unknown-filter }}">text</div>',
			'Unknown filter in script'    => '<script>var x = "{{ val|unknown }}";</script>',
			'Unknown filter in style'     => '<style>.{{ cls|not-a-filter }} {}</style>',
			'Unknown filter in body'      => '<div>{{ value|custom }}</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Unknown filter should be blocked: $description" );
			$errorString = implode( ' ', $errors );
			$this->assertStringContainsStringIgnoringCase( 'filter', $errorString, "Error should mention filter: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testWrongContextFilterBlocked() {
		$templates = [
			'CSS filter in script'    => '<script>var x = "{{ val|css-id }}";</script>',
			'JS filter in style'      => '<style>.{{ cls|js-string }} {}</style>',
			'url filter in script'    => '<script>var x = "{{ u|url }}";</script>',
			'plain-text in script'    => '<script>var x = "{{ t|plain-text }}";</script>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::validateTemplate( $template );
			$this->assertNotEmpty( $errors, "Wrong-context filter should be blocked: $description" );
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
			'known filter in attribute' => '<div class="{{ cls|css-id }}">text</div>',
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
			'Script with only section markers' => '<script>{{#items}}var x = 1;{{/items}}</script>',
			'Script with only comment' => '<script>{{! this is a comment }}var x = 1;</script>',
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
