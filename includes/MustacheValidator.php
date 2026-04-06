<?php

namespace MediaWiki\Extension\Mustache;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class MustacheValidator {

	public static function validateTemplate( string $template ): array {
		$errors = [];

		// Check {{{, {{&, and {{=
		if ( preg_match( '/\{\{[{&=]/', $template ) > 0 ) {
			$errors['raw-interpolation'][] = '';
		}

		$allowedFilters = MustacheFilters::getBuiltinFilters();

		foreach ( MustacheFilters::parseInterpolations( $template ) as [ $name, $filters ] ) {
			if ( count( $filters ) === 0 ) {
				continue;
			}
			if ( count( $filters ) > 1 ) {
				$errors['unknown-filter'][] = implode( '|', $filters );
				continue;
			}
			$filter = $filters[0];
			if ( isset( $allowedFilters[ $filter ] ) ) {
				continue;
			}
			$errors['unknown-filter'][] = $filter;
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
		] );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		$errors = array_merge( $errors, $formatter->getErrors() );

		return self::formatErrors( $errors );
	}

	private static function formatErrors( array $errors ): array {
		$messages = [];

		foreach ( $errors as $key => $value ) {
			if (
				$key === 'dangerous-attributes' ||
				$key === 'attribute-name-interpolation' ||
				$key === 'unknown-filter' ||
				$key === 'attribute-filter-required'
			) {
				foreach ( $value as $error ) {
					$messages[] = wfMessage( 'mustache-error-' . $key, $error )
						->inContentLanguage()->text();
				}
			} else {
				$messages[] = wfMessage( 'mustache-error-' . $key )
					->inContentLanguage()->text();
			}
		}

		return $messages;
	}
}
