<?php

use MediaWiki\Extension\Mustache\MustacheCache;
use MediaWiki\Extension\Mustache\MustacheRenderer;
use MediaWiki\MediaWikiServices;
use Mustache\Cache\FilesystemCache;

/** @phpcs-require-sorted-array */
return [
	'Mustache.Renderer' => static function ( MediaWikiServices $services ): MustacheRenderer {
		$config = $services->getMainConfig();
		$cacheType = $config->get( 'MustacheCacheType' );
		$cacheDir = $config->get( 'MustacheCacheDir' );

		$cache = match ( $cacheType ) {
			'mediawiki' => new MustacheCache(
				$services->getObjectCacheFactory()->getLocalClusterInstance()
			),
			'filesystem' => new FilesystemCache( $cacheDir ),
			default => null,
		};

		return new MustacheRenderer( $cache );
	},
];
