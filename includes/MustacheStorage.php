<?php

namespace MediaWiki\Extension\Mustache;

class MustacheStorage {

	private static array $renderings = [];

	public static function store( string $html ): string {
		$id = wfRandomString( 16 );
		self::$renderings[$id] = $html;
		return $id;
	}

	public static function get( string $id ): ?string {
		return self::$renderings[$id] ?? null;
	}
}
