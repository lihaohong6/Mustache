<?php

use MediaWiki\Extension\Mustache\MustacheDataParser;

class ArgumentParserTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseSimpleArguments() {
		$args = [
			'',
			'name=John Doe',
			'age=30',
			'city=New York'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertSame( 'John Doe', $result['name'] );
		$this->assertSame( '30', $result['age'] );
		$this->assertSame( 'New York', $result['city'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseJsonArray() {
		$args = [
			'',
			'items=["Apple","Banana","Orange"]'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['items'] );
		$this->assertSame( 'Apple', $result['items'][0] );
		$this->assertSame( 'Banana', $result['items'][1] );
		$this->assertSame( 'Orange', $result['items'][2] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseJsonObject() {
		$args = [
			'',
			'user={"name":"John","email":"john@example.com","age":30}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['user'] );
		$this->assertSame( 'John', $result['user']['name'] );
		$this->assertSame( 'john@example.com', $result['user']['email'] );
		$this->assertSame( 30, $result['user']['age'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseNestedJsonObject() {
		$args = [
			'',
			'data={"name":"Test","address":{"street":"123 Main St","city":"New York","zip":"10001"}}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['data'] );
		$this->assertSame( 'Test', $result['data']['name'] );
		$this->assertIsArray( $result['data']['address'] );
		$this->assertSame( '123 Main St', $result['data']['address']['street'] );
		$this->assertSame( 'New York', $result['data']['address']['city'] );
		$this->assertSame( '10001', $result['data']['address']['zip'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseArrayOfObjects() {
		$args = [
			'',
			'users=[{"id":1,"name":"John"},{"id":2,"name":"Jane"},{"id":3,"name":"Bob"}]'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['users'] );
		$this->assertCount( 3, $result['users'] );
		$this->assertSame( 1, $result['users'][0]['id'] );
		$this->assertSame( 'John', $result['users'][0]['name'] );
		$this->assertSame( 2, $result['users'][1]['id'] );
		$this->assertSame( 'Jane', $result['users'][1]['name'] );
		$this->assertSame( 3, $result['users'][2]['id'] );
		$this->assertSame( 'Bob', $result['users'][2]['name'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseMixedArguments() {
		$args = [
			'',
			'title=Hello World',
			'enabled=true',
			'count=42',
			'items=["a","b","c"]',
			'meta={"author":"Test","version":"1.0"}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertSame( 'Hello World', $result['title'] );
		$this->assertSame( 'true', $result['enabled'] );
		$this->assertSame( '42', $result['count'] );
		$this->assertIsArray( $result['items'] );
		$this->assertSame( 'a', $result['items'][0] );
		$this->assertIsArray( $result['meta'] );
		$this->assertSame( 'Test', $result['meta']['author'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseInvalidJson() {
		$args = [
			'',
			'data={"invalid": json}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsString( $result['data'] );
		$this->assertStringContainsString( 'JSON parse error', $result['data'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseBooleanAndNull() {
		$args = [
			'',
			'isActive={"active":true,"deleted":null}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['isActive'] );
		$this->assertTrue( $result['isActive']['active'] );
		$this->assertNull( $result['isActive']['deleted'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseNumericValues() {
		$args = [
			'',
			'numbers={"int":42,"float":3.14,"negative":-10}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['numbers'] );
		$this->assertIsInt( $result['numbers']['int'] );
		$this->assertSame( 42, $result['numbers']['int'] );
		$this->assertIsFloat( $result['numbers']['float'] );
		$this->assertSame( 3.14, $result['numbers']['float'] );
		$this->assertSame( -10, $result['numbers']['negative'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseEmptyArrayAndObject() {
		$args = [
			'',
			'emptyArray=[]',
			'emptyObject={}',
			'simple={}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['emptyArray'] );
		$this->assertEmpty( $result['emptyArray'] );
		$this->assertIsArray( $result['emptyObject'] );
		$this->assertEmpty( $result['emptyObject'] );
		$this->assertIsArray( $result['simple'] );
		$this->assertEmpty( $result['simple'] );
	}

}
