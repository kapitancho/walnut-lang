<?php

namespace Walnut\Lang\Implementation\Type;

use InvalidArgumentException;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class BooleanTypeTest extends BaseProgramTestHelper {

	public function testProperties(): void {
		$boolean = $this->typeRegistry->boolean();
		$this->assertTrue(
			$boolean->enumeration()->name()->equals(new TypeNameIdentifier('Boolean'))
		);
		$this->assertCount(2, $boolean->subsetValues());
		$this->assertCount(2, $boolean->values());
	}

	public function testEmptySubset(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->typeRegistry->boolean()->subsetType([]);
	}

	public function testInvalidSubsetValue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->typeRegistry->boolean()->subsetType([new EnumValueIdentifier('Wrong')]);
	}
}