<?php

namespace Walnut\Lang\Test\Implementation\Value;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class NullValueTest extends TestCase {

	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
	}
	public function testNullValue(): void {
		$nullType = $this->typeRegistry->null();
		$nullValue = $this->valueRegistry->null();

		self::assertEquals($nullType, $nullValue->type());
		self::assertNull($nullValue->literalValue());
		self::assertTrue($nullValue->equals($nullType->value()));
		self::assertFalse($nullValue->equals($this->valueRegistry->true()));
	}
}