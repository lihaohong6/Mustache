<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContentHandler;

class MustacheContentHandler extends TextContentHandler {

	public function __construct() {
		parent::__construct( 'mustache', [ CONTENT_FORMAT_HTML ] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getContentClass() {
		return MustacheContent::class;
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
	public function serializeContent( Content $content, $format = null ) {
		return parent::serializeContent( $content, CONTENT_FORMAT_HTML );
	}
}
