<?php

namespace MediaWiki\Extension\Mustache;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class MustacheValidator {

	public static function validateTemplate( string $template ): array {
		return self::formatErrors( self::getValidationErrors( $template ) );
	}

	public static function getValidationErrors( string $template ): array {
		$errors = [];

		// Check {{{, {{&, and {{=
		if ( preg_match( '/\{\{[{&=]/', $template ) > 0 ) {
			$errors[] = [
				'key' => 'raw-interpolation',
				'params' => []
			];
		}

		$allowedFilters = MustacheFilters::getBuiltinFilters();

		foreach ( MustacheFilters::parseInterpolations( $template ) as [ $name, $filters ] ) {
			if ( count( $filters ) === 0 ) {
				continue;
			}
			if ( count( $filters ) > 1 ) {
				$errors[] = [
					'key' => 'unknown-filter',
					'params' => [ implode( '|', $filters ) ]
				];
				continue;
			}
			$filter = $filters[0];
			if ( isset( $allowedFilters[ $filter ] ) ) {
				continue;
			}
			$errors[] = [
				'key' => 'unknown-filter',
				'params' => [ $filter ]
			];
		}

		$formatter = new MustacheValidationFormatter();
		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer, [
			'ignoreErrors' => true,
			'ignoreNulls' => true,
		] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $template, [
			'ignoreErrors' => true,
			'ignoreCharRefs' => true,
			'ignoreNulls' => true,
			'skipPreprocess' => true,
		] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		$errors = array_merge( $errors, $formatter->getErrors() );

		return $errors;
	}

	private static function formatErrors( array $errors ): array {
		$messages = [];

		foreach ( $errors as $error ) {
			$messages[] = wfMessage( 'mustache-error-' . $error['key'], ...$error['params'] )
				->inContentLanguage()->text();
		}

		return $messages;
	}
}
