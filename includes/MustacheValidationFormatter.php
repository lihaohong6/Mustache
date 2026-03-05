<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Tidy\RemexCompatFormatter;
use Wikimedia\RemexHtml\Serializer\SerializerNode;

class MustacheValidationFormatter extends RemexCompatFormatter {

	private array $errors = [];

	public function __construct( array $options = [] ) {
		parent::__construct( $options );
		$this->errors = [];
	}

	public function characters( SerializerNode $parent, $text, $start, $length ) {
		$actualText = substr( $text, $start, $length );

		$parentTag = strtolower( $parent->name ?? '' );
		$noInterpolationTags = [
			'script' => true,
			'style' => true,
		];

		if ( isset( $noInterpolationTags[ $parentTag ]) && str_contains( $actualText, '{{' ) ) {
			$this->errors["$parentTag-interpolation"][] = "";
		}

		return parent::characters( $parent, $text, $start, $length );
	}

	public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
		$tagName = strtolower( $node->name );

		foreach ( $node->attrs->getValues() as $attrName => $attrValue ) {
			$attrNameLower = strtolower( $attrName );

			if ( str_contains( $attrName, '{{') ) {
				$this->errors['attribute-name-interpolation'][] = [ $tagName ];
			}

			if ( str_contains( $attrValue, '{{' ) ) {
				if ( !self::isAttributeSafeForInterpolation( $attrNameLower ) ) {
					$this->errors['dangerous-attributes'][] = [ $attrName, $tagName ];
				}
			}
		}

		return parent::element( $parent, $node, $contents );
	}

	/**
	 * Get all validation errors.
	 *
	 * @return array Array of errors grouped by type
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	private static function isAttributeSafeForInterpolation( string $attrName ): bool {
		$allowedAttributes = [
			'id',
			'class',
			'title',
			'alt',
			'style',
			'href',
			'src',
			'name',
			'for',
			'value',
			'placeholder',
		];

		if ( in_array( $attrName, $allowedAttributes, true ) ) {
			return true;
		}

		if ( str_starts_with( $attrName, 'data-' ) || str_starts_with( $attrName, 'aria-' ) ) {
			return true;
		}

		return false;
	}
}
