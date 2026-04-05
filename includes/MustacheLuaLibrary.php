<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaError;

class MustacheLuaLibrary extends LibraryBase {

	public function register() {
		$lib = [
			'render' => [ $this, 'mustacheRender' ],
		];
		return $this->getEngine()->registerInterface( __DIR__ . '/mw.ext.mustache.lua', $lib, [] );
	}

	public function mustacheRender( string $templateName, array $data ): array {
		$this->checkType( 'render', 1, $templateName, 'string' );
		$this->checkTypeOptional( 'render', 2, $data, 'table', [] );

		$result = MustacheUtils::loadTemplateContent(
			trim( $templateName ),
			NS_MUSTACHE,
			$this->getParser()
		);

		if ( !$result['success'] ) {
			$msgKey = 'mustache-error-' . $result['errorType'];
			throw new LuaError( wfMessage( $msgKey, $templateName )->inContentLanguage()->text() );
		}

		$template = $result['content'];

		$phpData = self::convertLuaTableToArray( $data );

		$html = MustacheRenderer::render( $template, $phpData );

		$marker = MustacheRenderer::storeForOutput( $this->getParser(), $html );

		return [ $marker ];
	}

	/**
	 * Convert 1-based Lua table to 0-based PHP array.
	 * Taken from the Cargo extension, which is also licensed under GPL v2.
	 * Similar code snippets appear in other extensions (e.g. Bucket) too. Not sure where it came from originally.
	 * Whether this snippet is copyrightable I'm not sure either since it could be derived by anyone who is
	 * aware of this issue.
	 *
	 * @param mixed $table
	 * @return mixed
	 */
	private static function convertLuaTableToArray( $table ) {
		if ( is_array( $table ) ) {
			$converted = [];
			foreach ( $table as $key => $value ) {
				if ( is_int( $key ) || is_string( $key ) ) {
					$new_key = is_int( $key ) ? $key - 1 : $key;
					$converted[$new_key] = self::convertLuaTableToArray( $value );
				}
			}
			return $converted;
		}
		return $table;
	}
}
