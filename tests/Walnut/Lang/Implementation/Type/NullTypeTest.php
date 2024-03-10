<?php

namespace Walnut\Lang\Test\Implementation\Type;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Implementation\Registry\TypeRegistry;

final class NullTypeTest extends TestCase {

	private readonly TypeRegistry $typeRegistry;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
	}
	public function testNullType(): void {
		$nullType = $this->typeRegistry->null();
		self::assertEquals('Null', $nullType->name()->identifier);
		self::assertNull($nullType->value()->literalValue());
		self::assertTrue($nullType->isSubtypeOf($this->typeRegistry->null()));
		self::assertFalse($nullType->isSubtypeOf($this->typeRegistry->boolean()));
	}
}