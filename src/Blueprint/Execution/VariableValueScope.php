<?php

namespace Walnut\Lang\Blueprint\Execution;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Value\Value;

interface VariableValueScope extends VariableScope {
	public function findVariable(VariableNameIdentifier $variableName): VariableValuePair|UnknownVariable;
	public function findValueOf(VariableNameIdentifier $variableName): Value|UnknownVariable;

	/** @throws UnknownContextVariable */
	public function getVariable(VariableNameIdentifier $variableName): VariableValuePair;

	/** @throws UnknownContextVariable */
	public function typedValueOf(VariableNameIdentifier $variableName): TypedValue;

	/** @throws UnknownContextVariable */
	public function valueOf(VariableNameIdentifier $variableName): Value;

	public function withAddedValues(VariableValuePair ... $pairs): VariableValueScope;
}