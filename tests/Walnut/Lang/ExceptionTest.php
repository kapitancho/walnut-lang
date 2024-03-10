<?php

namespace Walnut\Lang;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Blueprint\Value\UnknownEnumerationValue;

final class ExceptionTest extends TestCase {

	public function testUnknownType(): void {
		$this->expectException(UnknownType::class);
		UnknownType::withName(new TypeNameIdentifier('X'));
	}

	public function testUnknownEnumerationValue(): void {
		$this->expectException(UnknownEnumerationValue::class);
		UnknownEnumerationValue::of(
			new TypeNameIdentifier('X'),
			new EnumValueIdentifier('Y')
		);
	}

}