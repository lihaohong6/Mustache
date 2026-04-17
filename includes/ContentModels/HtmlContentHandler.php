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

	/**
	 * @inheritDoc
	 */
	protected function getContentClass() {
		return HtmlContent::class;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsRedirects() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isSupportedFormat( $format ): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function validateSave( Content $content, ValidationParams $validationParams ): StatusValue {
		// Do not validate HTML for now.
		return StatusValue::newGood();
	}

	/**
	 * @inheritDoc
	 */
	public function serializeContent( Content $content, $format = null ) {
		return parent::serializeContent( $content, CONTENT_FORMAT_HTML );
	}
}
