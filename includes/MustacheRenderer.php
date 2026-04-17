<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Parser;
use Mustache\Cache;
use Mustache\Engine;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class MustacheRenderer {

	private const MARKER_SUFFIX = "_END</div>";
	private const MARKER_PREFIX = "<div>\xEF\xBF\xBCMUSTACHE_";

	private Engine $engine;

	public function __construct( ?Cache $cache = null ) {
		$options = [
			'entity_flags' => ENT_QUOTES,
			'lambdas' => true,
			'dynamic_names' => false,
			'inheritance' => false,
			'strict_callables' => true,
			'pragmas' => [ Engine::PRAGMA_FILTERS ],
			'helpers' => MustacheFilters::getBuiltinFilters(),
			'escape' => static function ( $value ) {
				if ( $value instanceof FilteredString ) {
					return (string)$value;
				}
				return htmlspecialchars( $value, ENT_SUBSTITUTE, 'UTF-8', false );
			},
		];
		if ( $cache !== null ) {
			$options['cache'] = $cache;
		}
		$this->engine = new Engine( $options );
	}

	public function render( string $template, array $data ): string {
		$rendered = $this->engine->render( $template, $data );
		return $this->sanitizeRenderedTemplate( $rendered );
	}

	public function sanitizeRenderedTemplate( string $rendered ): string {
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
