<?php

namespace Walnut\Lang;

use JsonException;
use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\IdentifierException;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;

final class IdentifierTest extends TestCase {

	private function jsonEncode(mixed $value): string {
		try {
			return json_encode($value, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return '';
		}
	}

	public function testEnumValueIdentifier(): void {
		self::assertEquals('X', (string)(new EnumValueIdentifier("X")));
		self::assertEquals('"X"', $this->jsonEncode(new EnumValueIdentifier("X")));
		self::assertTrue((new EnumValueIdentifier("X"))->equals(new EnumValueIdentifier("X")));
		self::assertNotNull(new EnumValueIdentifier("ItShouldStartWithUppercaseAndContainAToZAnd0To9"));
		$this->expectException(IdentifierException::class);
		new EnumValueIdentifier("itShouldNotStartWithLowercase");
	}

	public function testPropertyNameIdentifier(): void {
		self::assertEquals('x', (string)(new PropertyNameIdentifier("x")));
		self::assertEquals('"x"', $this->jsonEncode(new PropertyNameIdentifier("x")));
		self::assertTrue((new PropertyNameIdentifier("x"))->equals(new PropertyNameIdentifier("x")));
		self::assertNotNull(new PropertyNameIdentifier("222"));
		self::assertNotNull(new PropertyNameIdentifier("ItShouldContainsAToZ0To9And_Underscore"));
		$this->expectException(IdentifierException::class);
		new PropertyNameIdentifier("OtherCharactersLike+AreNotAllowed");
	}

	public function testMethodNameIdentifier(): void {
		self::assertEquals('x', (string)(new MethodNameIdentifier("x")));
		self::assertEquals('"x"', $this->jsonEncode(new MethodNameIdentifier("x")));
		self::assertTrue((new MethodNameIdentifier("x"))->equals(new MethodNameIdentifier("x")));
		self::assertNotNull(new MethodNameIdentifier("222"));
		self::assertNotNull(new MethodNameIdentifier("ItShouldContainsAToZ0To9And_Underscore"));
		$this->expectException(IdentifierException::class);
		new MethodNameIdentifier("OtherCharactersLike+AreNotAllowed");
	}

	public function testTypeNameIdentifier(): void {
		self::assertEquals('X', (string)(new TypeNameIdentifier("X")));
		self::assertEquals('"X"', $this->jsonEncode(new TypeNameIdentifier("X")));
		self::assertTrue((new TypeNameIdentifier("X"))->equals(new TypeNameIdentifier("X")));
		self::assertNotNull(new TypeNameIdentifier("ItShouldStartWithUppercaseAndContainAToZAnd0To9"));
		$this->expectException(IdentifierException::class);
		new TypeNameIdentifier("itShouldNotStartWithLowercase");
	}

	public function testVariableNameIdentifier(): void {
		self::assertEquals('x', (string)(new VariableNameIdentifier("x")));
		self::assertEquals('"x"', $this->jsonEncode(new VariableNameIdentifier("x")));
		//self::assertTrue((new VariableNameIdentifier("x"))->equals(new VariableNameIdentifier("x")));
		self::assertNotNull(new VariableNameIdentifier("$"));
		self::assertNotNull(new VariableNameIdentifier("#"));
		self::assertNotNull(new VariableNameIdentifier("#recordProperty"));
		self::assertNotNull(new VariableNameIdentifier("itShouldStartWithLowercaseAndContainAToZAnd0To9"));
		$this->expectException(IdentifierException::class);
		new VariableNameIdentifier("ItShouldNotStartWithUppercase");
	}
}