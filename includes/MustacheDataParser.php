<?php

namespace MediaWiki\Extension\Mustache;

use HJSON\HJSONException;
use HJSON\HJSONParser;

class MustacheDataParser {

	public static function parseArguments( array $args ): array {
		$data = [];

		for ( $i = 1; $i < count( $args ); $i++ ) {
			$arg = $args[$i];

			if ( preg_match( '/^([^=]+)=(.*)$/s', $arg, $matches ) ) {
				$key = trim( $matches[1] );
				$value = trim( $matches[2] );

				$parsed = self::parseValue( $value );
				$data[$key] = $parsed[1] ?? $parsed[0];
			}
		}

		return $data;
	}

	/**
	 * Parse a value, detecting and decoding JSON if present.
	 *
	 * @param string $value The value to parse
	 * @return array An array where the first value is a string or array, representing the parsed value.
	 * The second value is an optional error string indicating that parsing has failed.
	 */
	public static function parseValue( string $value ): array {
		$trimmed = trim( $value );

		if ( $trimmed === '' ) {
			return [ '' ];
		}

		$firstChar = $trimmed[0];
		$lastChar = $trimmed[strlen( $trimmed ) - 1];

		$hasJsonDelimiters = ( $firstChar === '{' && $lastChar === '}' ) ||
			( $firstChar === '[' && $lastChar === ']' );

		if ( !$hasJsonDelimiters ) {
			return [ $value ];
		}

		$parser = new HJSONParser();
		try {
			$decoded = $parser->parse( $trimmed, [ 'associative' => true ] );
		} catch ( HJSONException $e ) {
			return [ '', '<span class="error">' . htmlspecialchars( $e->getMessage() ) . '</span>' ];
		}
		return [ $decoded ];
	}
}
