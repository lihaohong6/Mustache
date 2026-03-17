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

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseCustomFormatMultiWordValues() {
		$args = [
			'',
			'items=[{name = Apple, value = $3 each}, {name = Banana, value = $1 each}, {name = Orange, value = $2 each}]'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['items'] );
		$this->assertCount( 3, $result['items'] );
		$this->assertSame( 'Apple', $result['items'][0]['name'] );
		$this->assertSame( '$3 each', $result['items'][0]['value'] );
		$this->assertSame( 'Banana', $result['items'][1]['name'] );
		$this->assertSame( '$1 each', $result['items'][1]['value'] );
		$this->assertSame( 'Orange', $result['items'][2]['name'] );
		$this->assertSame( '$2 each', $result['items'][2]['value'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseCustomFormatMixedWithJson() {
		$args = [
			'',
			'title=Product List',
			'items=[{name = Apple, value = $3 each}, {name = Banana, value = $1 each}]',
			'meta={"count":2,"total":3}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertSame( 'Product List', $result['title'] );
		$this->assertIsArray( $result['items'] );
		$this->assertCount( 2, $result['items'] );
		$this->assertSame( 'Apple', $result['items'][0]['name'] );
		$this->assertSame( '$3 each', $result['items'][0]['value'] );
		$this->assertSame( 'Banana', $result['items'][1]['name'] );
		$this->assertSame( '$1 each', $result['items'][1]['value'] );
		$this->assertIsArray( $result['meta'] );
		$this->assertSame( 2, $result['meta']['count'] );
		$this->assertSame( 3, $result['meta']['total'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseCustomFormatWithNestedStructures() {
		$args = [
			'',
			'data={title = Test, products = [{name = Apple, price = $3 each}, {name = Banana, price = $1 each}]}'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['data'] );
		$this->assertSame( 'Test', $result['data']['title'] );
		$this->assertIsArray( $result['data']['products'] );
		$this->assertCount( 2, $result['data']['products'] );
		$this->assertSame( 'Apple', $result['data']['products'][0]['name'] );
		$this->assertSame( '$3 each', $result['data']['products'][0]['price'] );
		$this->assertSame( 'Banana', $result['data']['products'][1]['name'] );
		$this->assertSame( '$1 each', $result['data']['products'][1]['price'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheDataParser::parseArguments
	 */
	public function testParseCustomFormatSpecialCharacters() {
		$args = [
			'',
			'items=[{name = Product A, price = $99.99}, {name = Product B, price = $49.99!}]'
		];

		$result = MustacheDataParser::parseArguments( $args );

		$this->assertIsArray( $result['items'] );
		$this->assertCount( 2, $result['items'] );
		$this->assertSame( 'Product A', $result['items'][0]['name'] );
		$this->assertSame( '$99.99', $result['items'][0]['price'] );
		$this->assertSame( 'Product B', $result['items'][1]['name'] );
		$this->assertSame( '$49.99!', $result['items'][1]['price'] );
	}

}
