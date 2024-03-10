<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class TypeTest extends BaseProgramTestHelper {

	private function callType(Value $value, TypeValue $expected): void {
		$this->testMethodCall(
			$this->expressionRegistry->constant($value),
			'type',
			$this->expressionRegistry->constant($this->valueRegistry->null()),
			$expected
		);
	}

    private function analyseCallType(Type $type, Type $expected): void {
        $this->testMethodCallAnalyse(
            $type,
            'type',
            $this->typeRegistry->null(),
            $expected
        );
    }

	public function testType(): void {
		$this->callType(
			$v = $this->valueRegistry->integer(123),
			$this->valueRegistry->type($this->typeRegistry->integerSubset([$v]))
		);

        $this->analyseCallType(
            $int = $this->typeRegistry->integer(-3, 42),
            $this->typeRegistry->type($int)
        );
	}
}
