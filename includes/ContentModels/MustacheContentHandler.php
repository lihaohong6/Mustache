<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\Content;
use MediaWiki\Content\TextContentHandler;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Exception\MWContentSerializationException;
use MediaWiki\Extension\Mustache\MustacheValidator;
use StatusValue;

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
	public function validateSave( Content $content, ValidationParams $validationParams ): StatusValue {
		$template = $content->getText();
		$errors = MustacheValidator::validateTemplate( $template );

		$status = StatusValue::newGood();
		if ( $errors ) {
			foreach ( $errors as $error ) {
				$status->fatal( new \RawMessage( '$1', [ $error ] ) );
			}
		}

		return $status;
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
	public function importTransform( $blob, $format = null ) {
		$errors = MustacheValidator::validateTemplate( $blob );
		if ( !empty( $errors ) ) {
			throw new MWContentSerializationException(
				wfMessage( 'mustache-import-error-invalid-content' )
					->inContentLanguage()->text()
			);
		}
		return $blob;
	}
}
