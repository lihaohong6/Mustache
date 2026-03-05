<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\TextContent;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;

class MustacheContent extends TextContent {

	/**
	 * Constructor.
	 *
	 * @param string $text Text content
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'mustache' );
	}

	/**
	 * Fill parser output.
	 *
	 * @param Title $title Title
	 * @param int $revId Revision ID
	 * @param ParserOutput $output Parser output
	 * @param array $options Options
	 */
	public function fillParserOutput( Title $title, $revId, ParserOutput $output, $options = [] ) {
		parent::fillParserOutput( $title, $revId, $output, $options );
	}
}
