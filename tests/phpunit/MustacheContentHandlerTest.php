<?php

namespace MediaWiki\Extension\Mustache\Tests;

use MediaWiki\Extension\Mustache\ContentModels\MustacheContent;
use MediaWiki\Extension\Mustache\ContentModels\MustacheContentHandler;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * @group Database
 * @covers MediaWiki\Extension\Mustache\ContentModels\MustacheContentHandler
 */
class MustacheContentHandlerTest extends MediaWikiIntegrationTestCase {

	public function testNoDirectEditing() {
		$handler = new MustacheContentHandler();

		$this->assertFalse( $handler->supportsDirectEditing() );
		$this->assertFalse( $handler->supportsDirectApiEditing() );
	}

	public static function provideTitles(): array {
		return [
			'Mustache namespace' => [ 'Mustache:Test' ],
			'Main namespace' => [ 'MustacheContentHandlerTest' ],
		];
	}

	/**
	 * @dataProvider provideTitles
	 */
	public function testSaveRejected( string $titleText ) {
		$title = Title::newFromText( $titleText );

		$status = $this->editPage( $title, new MustacheContent( '{{x}}' ) );

		$this->assertStatusError( 'content-not-allowed-here', $status );
		$this->assertFalse( $title->exists( IDBAccessObject::READ_LATEST ) );
	}
}
