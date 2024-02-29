<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class VariablePair {
	public function __construct(
		public VariableNameIdentifier $variableName,
		public Type                   $variableType,
	) {}
}