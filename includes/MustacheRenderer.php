<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Parser;
use Mustache\Engine;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class MustacheRenderer {

	public static string $markerSuffix = "_END</div>";
	public static string $markerPrefix = "<div>\xEF\xBF\xBCMUSTACHE_";

	public static function render( string $template, array $data ): string {
		$engine = new Engine( [
			'entity_flags' => ENT_QUOTES,
			// Lambdas required by PRAGMA_FILTERS
			'lambdas' => true,
			'dynamic_names' => false,
			'inheritance' => false,
			'strict_callables' => true,
			'pragmas' => [ Engine::PRAGMA_FILTERS ],
			'helpers' => MustacheFilters::getBuiltinFilters(),
			'escape' => function ( $value ) {
				return htmlspecialchars($value, ENT_SUBSTITUTE, 'UTF-8', false);
			},
		] );
		$rendered = $engine->render( $template, $data );
		return self::sanitizeRenderedTemplate( $rendered );
	}

	public static function sanitizeRenderedTemplate( string $rendered ): string {
		$formatter = new MustacheSanitizingFormatter();
		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer, [
			'ignoreErrors' => true,
			'ignoreNulls' => true,
		] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $rendered, [
			'ignoreErrors' => true,
			'ignoreCharRefs' => true,
			'ignoreNulls' => true,
			'skipPreprocess' => true,
		] );
		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );
		return $serializer->getResult();
	}

	public static function storeHtmlWithMarker( Parser $parser, string $html ): string {
		$marker = self::$markerPrefix . wfRandomString( 16 ) . self::$markerSuffix;
		$parser->getOutput()->appendExtensionData( 'mustacheRenderings', $marker . '|' . $html );
		return $marker;
	}
}
