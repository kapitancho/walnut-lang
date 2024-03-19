<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\ConstantExpression as ConstantExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ConstantExpression implements ConstantExpressionInterface, JsonSerializable {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private Value $value
	) {}

	public function value(): Value {
		return $this->value;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		if ($this->value instanceof FunctionValue) {
			$this->value->analyse($variableScope);
		}
		return new ExecutionResultContext(
			$this->value->type(),
			$this->typeRegistry->nothing(),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$value = $this->value;
		if ($value instanceof FunctionValue) {
			$value = $value->withVariableValueScope($variableValueScope);
		}
		return new ExecutionResultValueContext(TypedValue::forValue($value), $variableValueScope);
	}

	public function __toString(): string {
		return (string)$this->value;
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'constant',
			'value' => $this->value
		];
	}
}