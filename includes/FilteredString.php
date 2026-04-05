<?php

namespace MediaWiki\Extension\Mustache;

/**
 * Returned by Mustache filters to signal that the value is already safe for its context
 * and need not be HTML-escaped by the renderer's escape function.
 */
class FilteredString {
	public function __construct( private readonly string $value ) {}

	public function __toString(): string {
		return $this->value;
	}
}
