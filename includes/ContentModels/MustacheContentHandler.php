<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContentHandler;
use MediaWiki\Title\Title;

/**
 * Placeholder that occupies the namespace and name in case Mustache templates are reintroduced in the future.
 * All pages are set to read-only and can only be deleted.
 */
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

	/**
	 * @inheritDoc
	 */
	public function supportsDirectEditing() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function canBeUsedOn( Title $title ) {
		return false;
	}
}
