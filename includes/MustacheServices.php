<?php

namespace MediaWiki\Extension\Mustache;

use MediaWiki\MediaWikiServices;

class MustacheServices {

	public static function wrap( MediaWikiServices $services ): self {
		return new self( $services );
	}

	public function __construct( private readonly MediaWikiServices $services ) {
	}

	public function getRenderer(): MustacheRenderer {
		return $this->services->get( 'Mustache.Renderer' );
	}
}
