<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class StateTypeTest extends BaseProgramTestHelper {

	public function testProperties(): void {
		$this->builder->typeBuilder()['addState']('M', $boolean = $this->typeRegistry->boolean());
		$type = $this->typeRegistry->state(new TypeNameIdentifier('M'));
		$this->assertEquals($boolean, $type->stateType());
	}

}