<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

class MustacheUtils {

	private static array $namespaceToContentModel = [
		NS_MUSTACHE => CONTENT_MODEL_MUSTACHE,
		NS_HTML => CONTENT_MODEL_HTML,
	];

	public static function loadTemplateContent( string $name, int $namespace, Parser $parser ): array {

		$name = trim( $name );

		if ( empty( $name ) ) {
			return [
				'success' => false,
				'errorType' => 'no-template',
				'name' => ''
			];
		}

		$title = Title::makeTitleSafe( $namespace, $name );

		if ( !$title ) {
			return [
				'success' => false,
				'errorType' => 'invalid-template',
				'name' => htmlspecialchars( $name )
			];
		}

		$titleText = $title->getFullText();

		$revRecord = $parser->fetchCurrentRevisionRecordOfTitle( $title );
		if ( !$revRecord ) {
			return [
				'success' => false,
				'errorType' => 'template-not-found',
				'name' => htmlspecialchars( $titleText )
			];
		}

		$content = $revRecord->getContent( SlotRecord::MAIN );
		if ( !$content ) {
			return [
				'success' => false,
				'errorType' => 'template-not-found',
				'name' => htmlspecialchars( $titleText )
			];
		}

		if ( MustacheUtils::$namespaceToContentModel[ $namespace ] !== $content->getModel() ) {
			return [
				'success' => false,
				'errorType' => 'wrong-content-model',
				'name' => htmlspecialchars( $titleText ),
			];
		}

		return [
			'success' => true,
			'content' => $content->getText()
		];
	}

	public static function formatTemplateError( string $errorType, string $name, string $messagePrefix ): string {
		$msgKey = $messagePrefix . $errorType;
		$params = $name ? [ $name ] : [];
		return '<span class="error">' .
			wfMessage( $msgKey, ...$params )->inContentLanguage()->text() .
			'</span>';
	}

	public static function extractTemplateName( array $args, PPFrame $frame ): string {
		if ( count( $args ) === 0 ) {
			return '';
		}
		return trim( $frame->expand( $args[0] ) );
	}
}
