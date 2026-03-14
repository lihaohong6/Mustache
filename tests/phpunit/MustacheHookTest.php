<?php

namespace MediaWiki\Extension\Mustache\Tests;

use MediaWiki\Extension\Mustache\MustacheHooks;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWikiIntegrationTestCase;

class MustacheHookTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderMustache
	 */
	public function testRenderMustacheWithNoTemplate() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [];

		$result = MustacheHooks::renderMustache( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-no-template)', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderMustache
	 */
	public function testRenderMustacheWithEmptyTemplateName() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [ '' ];

		$frame->expects( $this->once() )
			->method( 'expand' )
			->with( $args[0] )
			->willReturn( '' );

		$result = MustacheHooks::renderMustache( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-no-template)', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderMustache
	 */
	public function testRenderMustacheWithInvalidTemplateName() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [ 'Invalid<Name' ];

		$frame->expects( $this->once() )
			->method( 'expand' )
			->with( $args[0] )
			->willReturn( 'Invalid<Name' );

		$result = MustacheHooks::renderMustache( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-invalid-template: ', $result );
		$this->assertStringContainsString( 'Invalid&lt;Name', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderHtml
	 */
	public function testRenderHtmlWithNoTemplate() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [];

		$result = MustacheHooks::renderHtml( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-no-template)', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderHtml
	 */
	public function testRenderHtmlWithEmptyTemplateName() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [ '' ];

		$frame->expects( $this->once() )
			->method( 'expand' )
			->with( $args[0] )
			->willReturn( '' );

		$result = MustacheHooks::renderHtml( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-no-template)', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::renderHtml
	 */
	public function testRenderHtmlWithInvalidTemplateName() {
		$parser = $this->createMock( Parser::class );
		$frame = $this->createMock( PPFrame::class );
		$args = [ 'Invalid>Name' ];

		$frame->expects( $this->once() )
			->method( 'expand' )
			->with( $args[0] )
			->willReturn( 'Invalid>Name' );

		$result = MustacheHooks::renderHtml( $parser, $frame, $args );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '(mustache-error-invalid-template: ', $result );
		$this->assertStringContainsString( 'Invalid&gt;Name', $result );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::onParserAfterTidy
	 */
	public function testOnParserAfterTidyWithNoRenderings() {
		$parser = $this->createMock( Parser::class );
		$text = '<p>Some content</p>';

		$parserOutput = $this->createMock( 'ParserOutput' );
		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( 'mustacheRenderings' )
			->willReturn( null );

		$parser->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$textAfter = $text;
		MustacheHooks::onParserAfterTidy( $parser, $textAfter );

		$this->assertSame( $text, $textAfter );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::onParserAfterTidy
	 */
	public function testOnParserAfterTidyWithEmptyRenderingsArray() {
		$parser = $this->createMock( Parser::class );
		$text = '<p>Some content</p>';

		$parserOutput = $this->createMock( 'ParserOutput' );
		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( 'mustacheRenderings' )
			->willReturn( [] );

		$parser->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$textAfter = $text;
		MustacheHooks::onParserAfterTidy( $parser, $textAfter );

		$this->assertSame( $text, $textAfter );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheHooks::onParserAfterTidy
	 */
	public function testOnParserAfterTidyReplacesMarkers() {
		$parser = $this->createMock( Parser::class );
		$marker1 = '<div>​MUSTACHE_test1_END</div>';
		$marker2 = '<div>​MUSTACHE_test2_END</div>';
		$html1 = '<div class="rendered-1">Content 1|</div>';
		$html2 = '<div class="rendered-2">Content 2</div>';

		$text = "<p>Before $marker1 Middle $marker2 After</p>";

		$parserOutput = $this->createMock( 'ParserOutput' );
		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( 'mustacheRenderings' )
			->willReturn( [ $marker1 . '|' . $html1 => true, $marker2 . '|' . $html2 => true ] );

		$parser->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		MustacheHooks::onParserAfterTidy( $parser, $text );

		$this->assertStringContainsString( $html1, $text );
		$this->assertStringContainsString( $html2, $text );
		$this->assertStringNotContainsString( $marker1, $text );
		$this->assertStringNotContainsString( $marker2, $text );
	}
}
