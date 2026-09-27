<?php

use MediaWiki\Extension\Mustache\MustacheRenderer;

/** @phpcs-require-sorted-array */
return [
	'Mustache.Renderer' => static function (): MustacheRenderer {
		return new MustacheRenderer();
	},
];
