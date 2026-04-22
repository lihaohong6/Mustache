<?php

namespace MediaWiki\Extension\Mustache\Tests;

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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Raw interpolation should be blocked: $description" );
			$errorString = implode( ' ', array_column( $errors, 'key' ) );
			$this->assertStringContainsString( 'raw', $errorString, "Error should mention raw interpolation: $description" );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Script interpolation should be blocked: $description" );
			$errorString = implode( ' ', array_column( $errors, 'key' ) );
			$this->assertStringContainsString( 'script', $errorString, "Error should mention script tag: $description" );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Style interpolation should be blocked: $description" );
			$errorString = implode( ' ', array_column( $errors, 'key' ) );
			$this->assertStringContainsString( 'style', $errorString, "Error should mention style tag: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testScriptTagWithJsFiltersAllowed() {
		$templates = [
			'js-string filter'          => '<script>var x = "{{ value|js-string }}";</script>',
			'js-identifier filter'      => '<script>var {{ name|js-identifier }} = 1;</script>',
			'js-string in function'     => '<script>function f() { alert("{{ msg|js-string }}"); }</script>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Script tag with JS filter should be allowed: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testStyleTagWithCssFiltersAllowed() {
		$templates = [
			'css-selector for class selector'    => '<style>.{{ cls|css-selector }} { color: red; }</style>',
			'css-selector for id selector'       => '<style>#{{ id|css-selector }} { font-size: 16px; }</style>',
			'css-value for property value' => '<style>.foo { color: {{ color|css-value }}; }</style>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Style tag with CSS filter should be allowed: $description" );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Unknown filter should be blocked: $description" );
			$errorString = implode( ' ', array_column( $errors, 'key' ) );
			$this->assertStringContainsString( 'filter', $errorString, "Error should mention filter: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testWrongContextFilterBlocked() {
		$templates = [
			'CSS filter in script'    => '<script>var x = "{{ val|css-selector }}";</script>',
			'JS filter in style'      => '<style>.{{ cls|js-string }} {}</style>',
			'url filter in script'    => '<script>var x = "{{ u|url }}";</script>',
			'plain-text in script'    => '<script>var x = "{{ t|plain-text }}";</script>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Dangerous attribute should be blocked: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testSafeAttributesAllowed() {
		$templates = [
			'id attribute with attribute filter' => '<div id="{{ id|attribute }}">text</div>',
			'class attribute with attribute filter' => '<div class="{{ cls|attribute }}">text</div>',
			'data-* attribute with attribute filter' => '<div data-value="{{ val|attribute }}">text</div>',
			'aria-* attribute with attribute filter' => '<div aria-label="{{ label|attribute }}">text</div>',
			// href with partial static prefix is not caught by the url-filter rule (doesn't start with {{)
			'href with partial interpolation' => '<a href="https://example.com/{{ path|attribute }}">link</a>',
			'style attribute with css-value filter' => '<div style="color: {{color|css-value}}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Safe attribute should be allowed: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testAttributeFilterRequiredInSafeAttributes() {
		$templates = [
			'unfiltered class' => '<div class="{{cls}}">text</div>',
			'wrong filter in id' => '<div id="{{ id|css-selector }}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Missing attribute filter should be blocked: $description" );
			$this->assertStringContainsString( 'attribute', implode( ' ', array_column( $errors, 'key' ) ) );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidationFormatter
	 */
	public function testCssValueFilterRequiredInStyleAttribute() {
		$templates = [
			'unfiltered style attribute' => '<div style="color: {{color}}">text</div>',
			'wrong filter in style attribute' => '<div style="color: {{color|attribute}}">text</div>',
		];

		foreach ( $templates as $description => $template ) {
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertNotEmpty( $errors, "Missing css-value filter in style attribute should be blocked: $description" );
			$allParams = array_merge( [], ...array_column( $errors, 'params' ) );
			$this->assertContains( 'css-value', $allParams, "Error should specify css-value filter: $description" );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Static content should be allowed: $description" );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Empty/whitespace templates should pass: $description" );
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidator::getValidationErrors
	 */
	public function testGetValidationErrorsReturnsRawStructure() {
		$errors = MustacheValidator::getValidationErrors( '<div>{{{raw}}}</div>' );
		$this->assertNotEmpty( $errors );
		$this->assertArrayHasKey( 'key', $errors[0] );
		$this->assertArrayHasKey( 'params', $errors[0] );
		$this->assertSame( 'raw-interpolation', $errors[0]['key'] );
		$this->assertSame( [], $errors[0]['params'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheValidator::getValidationErrors
	 */
	public function testGetValidationErrorsReturnsEmptyForValidTemplate() {
		$errors = MustacheValidator::getValidationErrors( '<div class="{{ cls|attribute }}">text</div>' );
		$this->assertSame( [], $errors );
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
			$errors = MustacheValidator::getValidationErrors( $template );
			$this->assertSame( [], $errors, "Special characters should not cause false positives: $description" );
		}
	}
}
