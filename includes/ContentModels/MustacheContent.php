<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\TextContent;

class MustacheContent extends TextContent {

	public function __construct( string $text ) {
		parent::__construct( $text, CONTENT_MODEL_MUSTACHE );
	}
}
