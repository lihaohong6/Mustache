<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\TextContent;

class MustacheContent extends TextContent {

	public function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_MUSTACHE );
	}

	public function isValid(): bool {
		// We could try to validate here, but trying to import an invalid mustache template will fail with a
		// cryptic error.
		return true;
		// return empty( MustacheValidator::validateTemplate( $this->getText() ) );
	}
}
