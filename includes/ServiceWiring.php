<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Mustache\MustacheRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\Tidy\RemexDriver;
use MediaWiki\Tidy\TidyDriverBase;

/** @phpcs-require-sorted-array */
return [
	'Mustache.Renderer' => static function (): MustacheRenderer {
		return new MustacheRenderer();
	},
	'Mustache.Tidy' => static function (): TidyDriverBase {
		$options = new ServiceOptions(
			RemexDriver::CONSTRUCTOR_OPTIONS, [ MainConfigNames::TidyConfig => [ 'pwrap' => false ] ]
		);
		return new RemexDriver( $options );
	},
];
