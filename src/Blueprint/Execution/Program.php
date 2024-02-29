<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

interface Program {
	public function callFunction(
		VariableNameIdentifier $functionName,
		Type $expectedParameterType,
		Type $expectedReturnType,
		Value $parameter
	): Value;
}