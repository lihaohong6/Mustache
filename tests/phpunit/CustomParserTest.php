<?php

use MediaWiki\Extension\Mustache\MustacheCustomParser;

class CustomParserTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseSimpleObject() {
		$input = '{ name = John, age = 30 }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'John', $result[0]['name'] );
		$this->assertSame( '30', $result[0]['age'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseSimpleArray() {
		$input = '[ apple, banana, orange ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertSame( 'apple', $result[0][0] );
		$this->assertSame( 'banana', $result[0][1] );
		$this->assertSame( 'orange', $result[0][2] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseNestedObject() {
		$input = '{ name = Test, address = { street = 123 Main St, city = New York } }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'Test', $result[0]['name'] );
		$this->assertIsArray( $result[0]['address'] );
		$this->assertSame( '123 Main St', $result[0]['address']['street'] );
		$this->assertSame( 'New York', $result[0]['address']['city'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseArrayOfObjects() {
		$input = '[ { name = John, age = 30 }, { name = Jane, age = 25 } ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 2, $result[0] );
		$this->assertSame( 'John', $result[0][0]['name'] );
		$this->assertSame( '30', $result[0][0]['age'] );
		$this->assertSame( 'Jane', $result[0][1]['name'] );
		$this->assertSame( '25', $result[0][1]['age'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseNestedArray() {
		$input = '[ [ 1, 2, 3 ], [ 4, 5, 6 ], [ 7, 8, 9 ] ]';
		$result = MustacheCustomParser::parse( $input )[0];

		$this->assertIsArray( $result );
		$this->assertCount( 3, $result );
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertCount( 3, $result[$i] );
			for ( $j = 0; $j < 3; $j++ ) {
				$this->assertSame( (string)( $i * 3 + $j + 1 ), $result[$i][$j] );
			}
		}
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseQuotedStrings() {
		$input = '{ name = "John Doe", city = "New York" }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'John Doe', $result[0]['name'] );
		$this->assertSame( 'New York', $result[0]['city'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseEmptyObject() {
		$input = '{}';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertEmpty( $result[0] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseEmptyArray() {
		$input = '[]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertEmpty( $result[0] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseComplexNestedStructure() {
		$input = '{ categories = [ { name = Fruit, items = [ Apple, Banana ] }, { name = Color, items = [ Red, Blue ] } ] }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertIsArray( $result[0]['categories'] );
		$this->assertCount( 2, $result[0]['categories'] );
		$this->assertSame( 'Fruit', $result[0]['categories'][0]['name'] );
		$this->assertCount( 2, $result[0]['categories'][0]['items'] );
		$this->assertSame( 'Apple', $result[0]['categories'][0]['items'][0] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseObjectWithMixedTypes() {
		$input = '{ string = hello, number = 123, boolean = true, null = null }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'hello', $result[0]['string'] );
		$this->assertSame( '123', $result[0]['number'] );
		$this->assertSame( 'true', $result[0]['boolean'] );
		$this->assertSame( 'null', $result[0]['null'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseEmptyString() {
		$input = '';
		$result = MustacheCustomParser::parse( $input );

		$this->assertSame( '', $result[0] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseStringWithoutDelimiters() {
		$input = 'simple string';
		$result = MustacheCustomParser::parse( $input );

		$this->assertSame( 'simple string', $result[0] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseWithWhitespace() {
		$input = '  {  name  =  John  ,  age  =  30  }  ';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'John', $result[0]['name'] );
		$this->assertSame( '30', $result[0]['age'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseTrailingComma() {
		$input = '[ a, b, c, ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertSame( 'a', $result[0][0] );
		$this->assertSame( 'b', $result[0][1] );
		$this->assertSame( 'c', $result[0][2] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseEmptyStringValue() {
		$input = '{ key1 = , key2 = something }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( '', $result[0]['key1'] );
		$this->assertSame( 'something', $result[0]['key2'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseEmptyQuotedStringValue() {
		$input = '{ key1 = "", key2 = something }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( '', $result[0]['key1'] );
		$this->assertSame( 'something', $result[0]['key2'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseArrayWithEmptyValues() {
		$input = '[ , a, , b ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 4, $result[0] );
		$this->assertSame( '', $result[0][0] );
		$this->assertSame( 'a', $result[0][1] );
		$this->assertSame( '', $result[0][2] );
		$this->assertSame( 'b', $result[0][3] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseNestedEmptyObject() {
		$input = '{ key = { nested = } }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertIsArray( $result[0]['key'] );
		$this->assertSame( '', $result[0]['key']['nested'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseAllEmptyObject() {
		$input = '{ key1 = , key2 = , key3 = }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertSame( '', $result[0]['key1'] );
		$this->assertSame( '', $result[0]['key2'] );
		$this->assertSame( '', $result[0]['key3'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseArrayInObjectWithEmptyValue() {
		$input = '{ items = [ , first, , second ] }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertIsArray( $result[0]['items'] );
		$this->assertCount( 4, $result[0]['items'] );
		$this->assertSame( '', $result[0]['items'][0] );
		$this->assertSame( 'first', $result[0]['items'][1] );
		$this->assertSame( '', $result[0]['items'][2] );
		$this->assertSame( 'second', $result[0]['items'][3] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseMultiWordValuesInObject() {
		$input = '{ name = Apple, value = $3 each }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'Apple', $result[0]['name'] );
		$this->assertSame( '$3 each', $result[0]['value'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseMultiWordValuesInArray() {
		$input = '[ $3 each, $1 each, $2 each ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertSame( '$3 each', $result[0][0] );
		$this->assertSame( '$1 each', $result[0][1] );
		$this->assertSame( '$2 each', $result[0][2] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseArrayOfObjectsWithMultiWordValues() {
		$input = '[{name = Apple, value = $3 each}, {name = Banana, value = $1 each}, {name = Orange, value = $2 each}]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 3, $result[0] );
		$this->assertSame( 'Apple', $result[0][0]['name'] );
		$this->assertSame( '$3 each', $result[0][0]['value'] );
		$this->assertSame( 'Banana', $result[0][1]['name'] );
		$this->assertSame( '$1 each', $result[0][1]['value'] );
		$this->assertSame( 'Orange', $result[0][2]['name'] );
		$this->assertSame( '$2 each', $result[0][2]['value'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseMultiWordValuesWithSpecialCharacters() {
		$input = '{ price = $99.99, description = Great product! }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( '$99.99', $result[0]['price'] );
		$this->assertSame( 'Great product!', $result[0]['description'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseNestedStructureWithMultiWordValues() {
		$input = '{ title = Product List, items = [ { name = Apple, price = $3 each }, { name = Banana, price = $1 each } ] }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'Product List', $result[0]['title'] );
		$this->assertIsArray( $result[0]['items'] );
		$this->assertCount( 2, $result[0]['items'] );
		$this->assertSame( 'Apple', $result[0]['items'][0]['name'] );
		$this->assertSame( '$3 each', $result[0]['items'][0]['price'] );
		$this->assertSame( 'Banana', $result[0]['items'][1]['name'] );
		$this->assertSame( '$1 each', $result[0]['items'][1]['price'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseMultiWordValueWithQuotedAndUnquoted() {
		$input = '{ name = "John Doe", occupation = Software Engineer, city = "New York" }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'John Doe', $result[0]['name'] );
		$this->assertSame( 'Software Engineer', $result[0]['occupation'] );
		$this->assertSame( 'New York', $result[0]['city'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseMultiWordValueWithTrailingWhitespace() {
		$input = '{ name = Apple, value = $3 each   }';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'Apple', $result[0]['name'] );
		$this->assertSame( '$3 each', $result[0]['value'] );
	}

	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parseValue
	 */
	/**
	 * @covers MediaWiki\Extension\Mustache\MustacheCustomParser::parse
	 */
	public function testParseArrayWithMixedMultiWordAndSingleWord() {
		$input = '[ apple, banana split, cherry, strawberry shortcake ]';
		$result = MustacheCustomParser::parse( $input );

		$this->assertIsArray( $result[0] );
		$this->assertCount( 4, $result[0] );
		$this->assertSame( 'apple', $result[0][0] );
		$this->assertSame( 'banana split', $result[0][1] );
		$this->assertSame( 'cherry', $result[0][2] );
		$this->assertSame( 'strawberry shortcake', $result[0][3] );
	}
}
