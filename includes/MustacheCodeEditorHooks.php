<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Title\Title;

class MustacheCodeEditorHooks {

	public static function onCodeEditorGetPageLanguage( Title $title, ?string &$lang, string $model, string $format ) {
		// TODO: should use Hooks::tempIsCodeMirrorBetaFeatureEnabled() to check for CodeMirror 6.
		//  However, as of 2026-03-12 CM6 does not provide lib.mode.htmlmixed:
		//  "CodeMirror 6 will eventually provide some or all of these modes"
		//  See https://www.mediawiki.org/wiki/Extension:CodeMirror
		if ( $model === 'mustache' || $model === 'html' ) {
			$lang = 'html';
			return false;
		}

		return true;
	}
}
