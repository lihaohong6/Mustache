<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class MustacheHooks {

	public function __construct( private readonly MustacheRenderer $renderer ) {
	}

	public static function onRegistration(): void {
		define( 'CONTENT_MODEL_HTML', 'html' );
		define( 'CONTENT_MODEL_MUSTACHE', 'mustache' );
	}

	public function onParserFirstCallInit( Parser $parser ): void {
		$parser->setFunctionHook(
			'mustache',
			[
				$this,
				'renderMustache'
			],
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'html',
			[
				$this,
				'renderHtml'
			],
			Parser::SFH_OBJECT_ARGS
		);
	}

	public function renderMustache( Parser $parser, PPFrame $frame, array $args ): string|array {
		$templateName = MustacheUtils::extractTemplateName( $args, $frame );
		$result = MustacheUtils::loadTemplateContent( $templateName, NS_MUSTACHE, $parser );

		if ( !$result['success'] ) {
			return MustacheUtils::formatTemplateError(
				$result['errorType'],
				$result['name'],
				'mustache-error-'
			);
		}

		$template = $result['content'];

		$data = [];
		$argCount = count( $args );
		for ( $i = 1; $i < $argCount; $i++ ) {
			$expandedArg = $frame->expand( $args[$i] );

			if ( preg_match( '/^([^=]+)=(.*)$/s', $expandedArg, $matches ) ) {
				$key = trim( $matches[1] );
				$value = trim( $matches[2] );
				$value = $parser->recursivePreprocess( $value );
				$parsed = MustacheDataParser::parseValue( $value );
				if ( count( $parsed ) === 1 ) {
					$data[ $key ] = $parsed[0];
				} else {
					return $parsed[1];
				}
			}
		}

		$html = $this->renderer->render( $template, $data );

		return $this->renderer->storeForOutput( $parser, $html );
	}

	public function renderHtml( Parser $parser, PPFrame $frame, array $args ): string|array {
		$pageName = MustacheUtils::extractTemplateName( $args, $frame );
		$result = MustacheUtils::loadTemplateContent( $pageName, NS_HTML, $parser );

		if ( !$result['success'] ) {
			return MustacheUtils::formatTemplateError(
				$result['errorType'],
				$result['name'],
				'mustache-error-'
			);
		}

		$html = $result['content'];

		return $this->renderer->storeForOutput( $parser, $html );
	}

	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ): void {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.mustache'] = MustacheLuaLibrary::class;
		}
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
