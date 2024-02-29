<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\UnknownContextVariable;
use Walnut\Lang\Blueprint\Execution\UnknownVariable;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\VariableNameExpression as VariableNameExpressionInterface;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final readonly class VariableNameExpression implements VariableNameExpressionInterface {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private VariableNameIdentifier $variableName
	) {}

	public function variableName(): VariableNameIdentifier {
		return $this->variableName;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		try {
			$type = $variableScope->typeOf($this->variableName);
		} catch (UnknownContextVariable) {
			$type = $this->valueRegistry->variables()->typeOf($this->variableName);
		}
		return new ExecutionResultContext(
			$type,
			$this->typeRegistry->nothing(),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$var = $variableValueScope->findVariable($this->variableName);
		$value = $var instanceof UnknownVariable ?
			$this->valueRegistry->variables()->typedValueOf($this->variableName) :
			$var->typedValue;
		return new ExecutionResultValueContext($value, $variableValueScope);
	}

	public function __toString(): string {
		return (string)$this->variableName;
	}
}