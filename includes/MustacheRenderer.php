<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Parser;

class MustacheRenderer {

	private const MARKER_SUFFIX = "_END</div>";
	private const MARKER_PREFIX = "<div>\xEF\xBF\xBCMUSTACHE_";

	public function storeForOutput( Parser $parser, string $html ): string {
		// Parsoid needs a special tag.
		if ( $parser->getOptions()->getUseParsoid() ) {
			$id = MustacheStorage::store( $html );
			return '<mustache-rendered id="' . $id . '" />';
		} else {
			$marker = self::MARKER_PREFIX . wfRandomString( 16 ) . self::MARKER_SUFFIX;
			$parser->getOutput()->appendExtensionData( 'mustacheRenderings', $marker . '|' . $html );
			return $marker;
		}
	}
}
