<?php

namespace MediaWiki\Extension\Mustache;

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
	 * Parse a value, detecting and decoding JSON or custom format if present.
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

		$hasDelimiters = ( $firstChar === '{' && $lastChar === '}' ) ||
			( $firstChar === '[' && $lastChar === ']' );

		if ( !$hasDelimiters ) {
			return [ $value ];
		}

		[
			$result,
			$error
		] = MustacheCustomParser::parse( $trimmed );

		if ( $result !== null ) {
			return [
				$result
			];
		}

		$decoded = json_decode( $trimmed, true );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			return [ $decoded ];
		}

		$jsonError = json_last_error_msg();

		if ( str_contains( $trimmed, '":' ) || str_contains( $trimmed, '" {' ) || str_contains( $trimmed, '" [' ) ) {
			return [
				null,
				"JSON parse error: $jsonError"
			];
		}

		return [
			null,
			wfMessage( 'mustache-custom-format-parser-error', $error )->inContentLanguage()->text()
		];
	}
}
