<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\TextContent;

class HtmlContent extends TextContent
{
    public function __construct($text) {
        parent::__construct($text, CONTENT_MODEL_HTML );
    }

	public function isValid(): bool {
		// Do not validate HTML for now.
		return true;
	}
}
