<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Value\Value;

interface ValueRegistryBuilder {
    public function addVariable(VariableNameIdentifier $name, Value $value): VariableValuePair;

	public function analyse(): void;
	public function build(): ValueRegistry;
}