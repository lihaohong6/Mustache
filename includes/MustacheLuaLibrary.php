<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaError;
use MediaWiki\MediaWikiServices;
use Mustache\Exception as MustacheException;

class MustacheLuaLibrary extends LibraryBase {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$lib = [
			'render' => [ $this, 'mustacheRender' ],
		];
		return $this->getEngine()->registerInterface( __DIR__ . '/mw.ext.mustache.lua', $lib, [] );
	}

	/**
	 * @throws LuaError
	 */
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

		$renderer = MustacheServices::wrap( MediaWikiServices::getInstance() )->getRenderer();
		try {
			$html = $renderer->render( $template, $phpData );
		} catch ( MustacheException $e ) {
			throw new LuaError( wfMessage( 'mustache-error-render-failed', $e->getMessage() )
				->inContentLanguage()->text() );
		}

		$marker = $renderer->storeForOutput( $this->getParser(), $html );

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
					$newKey = is_int( $key ) ? $key - 1 : $key;
					$converted[$newKey] = self::convertLuaTableToArray( $value );
				}
			}
			return $converted;
		}
		return $table;
	}
}
