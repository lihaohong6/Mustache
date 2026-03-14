<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContentHandler;
use MediaWiki\Content\ValidationParams;
use StatusValue;

class HtmlContentHandler extends TextContentHandler {
	public function __construct() {
		parent::__construct( 'html', [ CONTENT_FORMAT_HTML ] );
	}

	protected function getContentClass() {
		return HtmlContent::class;
	}

	public function supportsRedirects() {
		return false;
	}

	public function isSupportedFormat( $format ): bool {
		return true;
	}

	public function validateSave( Content $content, ValidationParams $validationParams ): StatusValue {
		// Do not validate HTML for now.
		return StatusValue::newGood();
	}

	public function serializeContent( Content $content, $format = null ) {
		return parent::serializeContent( $content, CONTENT_FORMAT_HTML );
	}
}
