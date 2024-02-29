<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;

interface VariableScope {
	/** @return string[] */
	public function variables(): array;
	/** @throws UnknownContextVariable */
	public function typeOf(VariableNameIdentifier $variableName): Type;

	public function withAddedVariablePairs(VariablePair ... $pairs): VariableScope;
}