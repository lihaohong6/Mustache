<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Sanitizer;
use MediaWiki\Tidy\RemexCompatFormatter;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

class MustacheSanitizingFormatter extends RemexCompatFormatter {

	public function __construct( array $options = [] ) {
		parent::__construct( $options );
	}

	public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
		$modifiedAttrs = [];

		foreach ( $node->attrs->getValues() as $attrName => $attrValue ) {
			$attrNameLower = strtolower( $attrName );

			if ( $attrNameLower === 'href' || $attrNameLower === 'src' ) {
				$attrValue = trim( $attrValue );
				$sanitizedValue = $this->sanitizeUrl( $attrValue );
				if ( $sanitizedValue !== $attrValue ) {
					$modifiedAttrs[$attrName] = $sanitizedValue;
				}
			} elseif ( $attrNameLower === 'style' ) {
				$sanitizedValue = Sanitizer::checkCss( $attrValue );
				if ( $sanitizedValue !== $attrValue ) {
					$modifiedAttrs[$attrName] = $sanitizedValue;
				}
			}
		}

		if ( sizeof( $modifiedAttrs ) > 0 ) {
			$node->attrs = new PlainAttributes( array_merge($node->attrs->getValues(), $modifiedAttrs ) );
		}

		return parent::element( $parent, $node, $contents );
	}

	private function sanitizeUrl( string $url ): string {
		$message = '';
		if ( !preg_match( '!^(https?:|/)!i', $url ) ) {
			return $message;
		}
		return $url;
	}
}
