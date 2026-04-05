<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Mustache\Parsoid;

use MediaWiki\Extension\Mustache\MustacheStorage;
use MediaWiki\Extension\Mustache\MustacheUtils;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class MustacheRenderedTagHandler extends ExtensionTagHandler {

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$attrs = $extApi->extArgsToArray( $args );
		$id = $attrs['id'] ?? '';
		$html = MustacheStorage::get( $id );

		if ( $html === null ) {
			$html = MustacheUtils::formatTemplateError( 'rendering-not-found', '', 'mustache-error-' );
		}

		return $extApi->htmlToDom( $html );
	}
}
