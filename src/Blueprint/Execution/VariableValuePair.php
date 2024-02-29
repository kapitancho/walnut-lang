<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;

final readonly class VariableValuePair {
	public function __construct(
		public VariableNameIdentifier $variableName,
		public TypedValue $typedValue
	) {}
}