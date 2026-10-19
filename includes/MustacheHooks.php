<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Tidy\TidyDriverBase;

class MustacheHooks {

	/** @var array<string, true> */
	private array $transcludedHtmlPages = [];

	public function __construct(
		private readonly MustacheRenderer $renderer,
		private readonly TidyDriverBase $tidy
	) {
	}

	public static function onRegistration(): void {
		define( 'CONTENT_MODEL_HTML', 'html' );
		define( 'CONTENT_MODEL_MUSTACHE', 'mustache' );
	}

	public function onParserFirstCallInit( Parser $parser ): void {
		$parser->setFunctionHook(
			'html',
			[
				$this,
				'renderHtml'
			],
			Parser::SFH_OBJECT_ARGS
		);
	}

	public function renderHtml( Parser $parser, PPFrame $frame, array $args ): string|array {
		// Marker is only expanded properly in OT_HTML.
		// Do not use marker in other modes of expansion, such as subst and Special:ExpandTemplate.
		$outputType = $parser->getOutputType();
		// Parsoid expands parser functions with OT_PREPROCESS
		$isUsingParsoid = $outputType === OT_PREPROCESS && $parser->getOptions()->getUseParsoid();
		if ( $outputType !== Parser::OT_HTML && !$isUsingParsoid ) {
			return '{{#html:' . implode( '|', array_map( $frame->expand( ... ), $args ) ) . '}}';
		}

		$pageName = MustacheUtils::extractTemplateName( $args, $frame );
		$result = MustacheUtils::loadTemplateContent( $pageName, NS_HTML, $parser );

		if ( !$result['success'] ) {
			return MustacheUtils::formatTemplateError(
				$result['errorType'],
				$result['name'],
				'mustache-error-'
			);
		}

		$key = $result['title']->getPrefixedDBkey();
		if ( isset( $this->transcludedHtmlPages[$key] ) && MustacheUtils::transcludeOnce( $args, $frame ) ) {
			return '';
		}
		$this->transcludedHtmlPages[$key] = true;

		$html = $this->tidy->tidy( $result['content'] );

		return $this->renderer->storeForOutput( $parser, $html );
	}

	public function onParserClearState( Parser $parser ): void {
		$this->transcludedHtmlPages = [];
	}

	public function onParserAfterTidy( Parser $parser, string &$text ): void {
		$replacements = $parser->getOutput()->getExtensionData( 'mustacheRenderings' );
		if ( !is_array( $replacements ) ) {
			return;
		}
		$map = [];
		foreach ( $replacements as $replacement => $boolValue ) {
			[
				$marker,
				$html
			] = explode( '|', $replacement, 2 );
			$map[$marker] = $html;
		}
		$text = strtr( $text, $map );
	}
}
