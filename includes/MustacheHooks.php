<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Extension\Mustache\ContentModels\MustacheContent;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use RawMessage;

class MustacheHooks {

	public static function onRegistration(): void {
		define( 'CONTENT_MODEL_HTML', 'html' );
		define( 'CONTENT_MODEL_MUSTACHE', 'mustache' );
	}

	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setFunctionHook(
			'mustache',
			[
				self::class,
				'renderMustache'
			],
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'html',
			[
				self::class,
				'renderHtml'
			],
			Parser::SFH_OBJECT_ARGS
		);
	}

	public static function renderMustache( Parser $parser, PPFrame $frame, array $args ): string|array {
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
		for ( $i = 1; $i < count( $args ); $i++ ) {
			$expandedArg = $frame->expand( $args[$i] );

			if ( preg_match( '/^([^=]+)=(.*)$/s', $expandedArg, $matches ) ) {
				$key = trim( $matches[1] );
				$value = trim( $matches[2] );
				$value = $parser->recursivePreprocess( $value );
				$parsed = MustacheDataParser::parseValue( $value );
				if ( sizeof( $parsed ) === 1 ) {
					$data[ $key ] = $parsed[0];
				} else {
					return $parsed[1];
				}
			}
		}

		$html = MustacheRenderer::render( $template, $data );

		return MustacheRenderer::storeHtmlWithMarker( $parser, $html );
	}

	public static function renderHtml( Parser $parser, PPFrame $frame, array $args ): string|array {
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

		return MustacheRenderer::storeHtmlWithMarker( $parser, $html );
	}

	public static function onEditFilterMergedContent(
		$context,
		$content,
		$status,
		$summary,
		$user,
		$minoredit
	) {
		$title = $context->getTitle();

		if ( $title->getNamespace() !== NS_MUSTACHE ) {
			return true;
		}

		if ( !( $content instanceof MustacheContent ) ) {
			return true;
		}

		$template = $content->getText();
		$errors = MustacheValidator::validateTemplate( $template );

		if ( $errors ) {
			$status->setOK( false );
			foreach ( $errors as $error ) {
				$status->fatal( new RawMessage( '$1', [ $error ] ) );
			}
			return false;
		}

		return true;
	}

	public static function addLuaLibrary( $engine, &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.mustache'] = MustacheLuaLibrary::class;
		}
	}

	public static function onParserAfterTidy( Parser $parser, string &$text ): void {
		$replacements = $parser->getOutput()->getExtensionData( 'mustacheRenderings' );
		if ( !is_array( $replacements ) ) {
			return;
		}
		foreach ( $replacements as $marker => $html ) {
			$text = str_replace( $marker, $html, $text );
		}
	}
}
