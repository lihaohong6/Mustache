<?php

namespace MediaWiki\Extension\Mustache\ContentModels;

use MediaWiki\Content\TextContent;

class HtmlContent extends TextContent
{
    public function __construct($text)
    {
        parent::__construct($text, 'html');
    }
}
