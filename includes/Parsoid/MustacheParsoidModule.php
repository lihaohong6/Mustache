<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Mustache\Parsoid;

use Wikimedia\Parsoid\Ext\ExtensionModule;

class MustacheParsoidModule implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'Mustache',
			'tags' => [
				[
					'name' => 'mustache-rendered',
					'handler' => [ 'class' => MustacheRenderedTagHandler::class ],
					'options' => [ 'hasWikitextInput' => false ],
				],
			],
		];
	}
}
