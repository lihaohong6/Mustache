<?php

namespace MediaWiki\Extension\Mustache;

use Exception;

class MustacheCustomParser {

	public static function parse( string $input ): array|string {
		$input = trim( $input );
		if ( $input === '' ) {
			return [
				'',
				null
			];
		}

		$firstChar = $input[0];
		if ( $firstChar !== '{' && $firstChar !== '[' ) {
			return [
				$input,
				null
			];
		}

		$tokens = self::tokenize( $input );
		if ( isset( $tokens['error'] ) ) {
			return [
				null,
				$tokens['error']
			];
		}

		$parser = new self( $tokens );
		try {
			$result = $parser->parseValue();
		} catch ( Exception $e ) {
			return [
				null,
				$e->getMessage()
			];
		}

		if ( $parser->pos !== count( $tokens ) ) {
			return [
				null,
				'unexpected token at position ' . $parser->pos
			];
		}

		return [
			$result,
			null
		];
	}

	private function __construct( private array $tokens ) {
	}

	private static function tokenize( string $input ): array {
		$tokens = [];
		$i = 0;
		$len = strlen( $input );

		while ( $i < $len ) {
			$char = $input[$i];

			if ( $char === '"' ) {
				$tokens[] = self::parseQuotedString( $input, $i );
			} elseif (
				$char === '{' ||
				$char === '}' ||
				$char === '[' ||
				$char === ']' ||
				$char === ',' ||
				$char === '='
			) {
				$tokens[] = $char;
				$i++;
			} elseif ( !ctype_space( $char ) ) {
				$tokens[] = self::parseUnquotedString( $input, $i );
			} else {
				$i++;
			}
		}

		return $tokens;
	}

	private static function parseQuotedString( string $input, int &$i ): string {
		$start = $i;
		$i++;
		$len = strlen( $input );
		$escaped = false;

		while ( $i < $len ) {
			$char = $input[$i];
			if ( $escaped ) {
				$escaped = false;
			} elseif ( $char === '\\' ) {
				$escaped = true;
			} elseif ( $char === '"' ) {
				$i++;
				return substr( $input, $start, $i - $start );
			}
			$i++;
		}

		return substr( $input, $start );
	}

	private static function parseUnquotedString( string $input, int &$i ): string {
		$start = $i;
		$len = strlen( $input );
		$delimiters = [
			'{',
			'}',
			'[',
			']',
			',',
			'=',
			'"'
		];

		while ( $i < $len ) {
			if ( ctype_space( $input[$i] ) || in_array( $input[$i], $delimiters, true ) ) {
				break;
			}
			$i++;
		}

		return substr( $input, $start, $i - $start );
	}

	private function parseValue( array $stopAt = [] ): mixed {
		$token = $this->currentToken();

		if ( $token === '{' ) {
			return $this->parseObject();
		}
		if ( $token === '[' ) {
			return $this->parseArray();
		}

		$defaultDelimiters = [
			'}',
			']',
			',',
			'='
		];

		if ( $token === null || in_array( $token, $stopAt, true ) || in_array( $token, $defaultDelimiters, true ) ) {
			return '';
		}

		return $this->parseString( $stopAt );
	}

	private function parseObject(): array {
		$this->consume( '{' );
		$obj = [];

		while ( $this->currentToken() !== '}' && $this->currentToken() !== null ) {
			$key = $this->parseValue( [ ',', '}' ] );

			if ( $this->currentToken() !== '=' ) {
				throw new Exception( 'Expected = after key' );
			}
			$this->consume( '=' );

			$value = $this->parseValue( [ ',', '}' ] );
			$obj[$key] = $value;

			if ( $this->currentToken() === ',' ) {
				$this->consume( ',' );
			}
		}

		$this->consume( '}' );
		return $obj;
	}

	private function parseArray(): array {
		$this->consume( '[' );
		$arr = [];

		while ( $this->currentToken() !== ']' && $this->currentToken() !== null ) {
			$arr[] = $this->parseValue( [ ',', ']' ] );

			if ( $this->currentToken() === ',' ) {
				$this->consume( ',' );
			}
		}

		$this->consume( ']' );
		return $arr;
	}

	private function parseString( array $stopAt = [] ): string {
		$token = $this->currentToken();
		if ( $token === null ) {
			return '';
		}

		if ( $token[0] === '"' ) {
			$this->advance();
			return substr( $token, 1, -1 );
		}

		$result = $token;
		$this->advance();

		while ( true ) {
			$nextToken = $this->currentToken();
			if ( $nextToken === null || in_array( $nextToken, $stopAt, true ) ) {
				break;
			}

			$defaultDelimiters = [
				'}',
				']',
				',',
				'=',
				'{',
				'['
			];

			if ( in_array( $nextToken, $defaultDelimiters, true ) ) {
				break;
			}

			if ( $nextToken[0] === '"' ) {
				break;
			}

			$result .= ' ' . $nextToken;
			$this->advance();
		}

		return $result;
	}

	private function currentToken(): string|null {
		return $this->tokens[$this->pos] ?? null;
	}

	private function consume( string $expected ): void {
		if ( $this->currentToken() === $expected ) {
			$this->advance();
		}
	}

	private function advance(): void {
		$this->pos++;
	}

	public int $pos = 0;

	private static function getError( string $message ): string {
		$message = wfMessage( 'mustache-custom-format-parser-error', htmlspecialchars( $message ) )
			->inContentLanguage()
			->text();
		return '<span class="error">' . $message . '</span>';
	}
}
