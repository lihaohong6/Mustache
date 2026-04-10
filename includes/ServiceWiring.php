<?php

use MediaWiki\Extension\Mustache\MustacheRenderer;
use MediaWiki\MediaWikiServices;

/** @phpcs-require-sorted-array */
return [
	'Mustache.Renderer' => static function ( MediaWikiServices $services ): MustacheRenderer {
		return new MustacheRenderer();
	},
];
