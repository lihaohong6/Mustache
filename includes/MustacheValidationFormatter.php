<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Tidy\RemexCompatFormatter;
use Wikimedia\RemexHtml\Serializer\SerializerNode;

class MustacheValidationFormatter extends RemexCompatFormatter {

	// Tags in which interpolation is restricted to designated filters
	private array $restrictedTags;

	private array $errors = [];

	public function __construct( array $options = [] ) {
		parent::__construct( $options );
		$filters = MustacheFilters::getBuiltinFilters();
		$allowedFilters = array_keys( $filters );
		$this->restrictedTags = [
			'style' => array_filter( $allowedFilters, static function ( $filter ) {
				return str_starts_with( $filter, 'css' );
			} ),
			'script' => array_filter( $allowedFilters, static function ( $filter ) {
				return str_starts_with( $filter, 'js' );
			} )
		];
	}

	public function characters( SerializerNode $parent, $text, $start, $length ) {
		$parentTag = strtolower( $parent->name ?? '' );

		if ( isset( $this->restrictedTags[$parentTag] ) ) {
			$actualText = substr( $text, $start, $length );
			$requiredFilters = $this->restrictedTags[$parentTag];

			foreach ( MustacheFilters::parseInterpolations( $actualText ) as [$varName, $filters] ) {
				// In restricted contexts, require at least one filter from the appropriate family.
				if ( empty( array_intersect( $filters, $requiredFilters ) ) ) {
					$this->errors["$parentTag-interpolation"][] = '';
				}
			}
		}

		return parent::characters( $parent, $text, $start, $length );
	}

	public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
		$tagName = strtolower( $node->name );

		foreach ( $node->attrs->getValues() as $attrName => $attrValue ) {
			$attrNameLower = strtolower( $attrName );

			if ( str_contains( $attrName, '{{' ) ) {
				$this->errors['attribute-name-interpolation'][] = [ $tagName ];
			}

			if ( str_contains( $attrValue, '{{' ) ) {
				if (
					in_array(
						$attrNameLower,
						[
							'href',
							'src'
						]
					) && str_starts_with( $attrValue, '{{' )
				) {
					// Start of href and src are prone to XSS through protocols such as javascript:
					foreach ( MustacheFilters::parseInterpolations( $attrValue ) as [$varName, $filters] ) {
						if ( count( $filters ) !== 1 || $filters[0] !== 'url' ) {
							$this->errors['url-filter-required'][] = [
								$attrNameLower,
								$tagName
							];
							break;
						}
					}
				} else {
					if ( !self::isAttributeSafeForInterpolation( $attrNameLower ) ) {
						$this->errors['dangerous-attributes'][] = [
							$attrName,
							$tagName
						];
					} else {
						if ( $attrNameLower === 'style' ) {
							$this->checkAttributeFilter( $tagName, $attrNameLower, $attrValue, 'css-value' );
						} else {
							$this->checkAttributeFilter( $tagName, $attrNameLower, $attrValue, 'attribute' );
						}
					}
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

	private function checkAttributeFilter(
		string $tagName,
		string $attrName,
		string $attrValue,
		string $requiredFilter
	): void {
		foreach ( MustacheFilters::parseInterpolations( $attrValue ) as [$varName, $filters] ) {
			if ( !in_array( $requiredFilter, $filters ) ) {
				$this->errors['attribute-filter-required'][] = [
					$attrName,
					$tagName,
					$requiredFilter
				];
				break;
			}
		}
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
