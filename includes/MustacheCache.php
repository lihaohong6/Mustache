<?php

namespace MediaWiki\Extension\Mustache;

use Mustache\Cache\AbstractCache;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * Mustache cache implementation backed by MediaWiki's BagOStuff.
 */
class MustacheCache extends AbstractCache {

	private BagOStuff $cache;
	private int $ttl;

	/**
	 * @param BagOStuff $cache MediaWiki object cache instance
	 * @param int $ttl Cache TTL in seconds; 0 means indefinite
	 */
	public function __construct( BagOStuff $cache, int $ttl = ExpirationAwareness::TTL_DAY ) {
		$this->cache = $cache;
		$this->ttl = $ttl;
	}

	/**
	 * @inheritDoc
	 */
	public function load( $key ): bool {
		if ( class_exists( $key, false ) ) {
			return true;
		}

		$value = $this->cache->get( $this->cache->makeKey( 'mustache-compiled', $key ) );
		if ( $value === false ) {
			return false;
		}

		// phpcs:ignore
		eval( '?>' . $value );
		// phpcs:enable
		return class_exists( $key, false );
	}

	/**
	 * @inheritDoc
	 */
	public function cache( $key, $value ): void {
		$this->cache->set(
			$this->cache->makeKey( 'mustache-compiled', $key ),
			$value,
			$this->ttl
		);
		// phpcs:ignore
		eval( '?>' . $value );
		// phpcs:enable
	}
}
