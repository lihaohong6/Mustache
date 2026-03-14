<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Content\ValidationParams;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Revision\SlotRecord;

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

	public static function onMultiContentSaveTest(
		$renderedRevision,
		$user,
		$summary,
		$flags,
		$status
	) {
		// IMPORTANT: this function is deliberately unused (not included in extension.json) because its features overlap
		// with existing functions (validateSave).
		$revision = $renderedRevision->getRevision();
		$content = $revision->getContent( SlotRecord::MAIN );
		if ( $content->getModel() !== CONTENT_MODEL_MUSTACHE && $content->getModel() !== CONTENT_MODEL_HTML ) {
			return true;
		}
		// Prevents users from touching Mustache content model pages outside of Mustache namespace
		// Isn't really necessary because Mustache parser function calls are restricted to a particular namespace
		$services = MediaWikiServices::getInstance();
		$permissionManager = $services->getPermissionManager();
		$canEditSiteCss = $permissionManager->userHasRight( $user, 'editsitecss' );
		$canEditSiteJs = $permissionManager->userHasRight( $user, 'editsitejs' );

		if ( !$canEditSiteCss || !$canEditSiteJs ) {
			$status->fatal( 'mustache-error-permission-denied' );
			return false;
		}

		// No validation required beyond this point. The validateSave hook in MustacheContentHandler
		// will take care of the rest.
		$handler = $content->getContentHandler();
		// MW expects a page identity. We know that validateSave doesn't need the page identity but provide one
		// to satisfy the linter anyway. Extremely dumb. The alternative is to make separate function calls but that
		// would duplicate functionality.
		$fakePageIdentity = new PageIdentityValue( 0, NS_MUSTACHE, "Test", false );
		$handlerStatus = $handler->validateSave(
			$content,
			new ValidationParams( $fakePageIdentity, $flags ) );

		if ( $handlerStatus->isOK() ) {
			return true;
		}

		foreach ( $handlerStatus->getMessages() as $msg ) {
			$status->fatal( $msg );
		}
		return false;
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
