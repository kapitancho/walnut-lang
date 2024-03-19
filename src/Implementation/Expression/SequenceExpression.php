<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\ReturnExpression;
use Walnut\Lang\Blueprint\Expression\SequenceExpression as SequenceExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;

final readonly class SequenceExpression implements SequenceExpressionInterface, JsonSerializable {

	/** @param list<Expression> $expressions */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private array $expressions
	) {}

	/** @return list<Expression> */
	public function expressions(): array {
		return $this->expressions;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$expressionType = $this->typeRegistry->nothing();
		$returnTypes = [];
		foreach($this->expressions as $expression) {
			$subRet = $expression->analyse($variableScope);

			$expressionType = $subRet->expressionType;
			$variableScope = $subRet->variableScope;
			$returnTypes[] = $subRet->returnType;
			if ($expression instanceof ReturnExpression) {
				break;
			}
		}
		return new ExecutionResultContext(
			$expressionType,
			$this->typeRegistry->union($returnTypes),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$result = TypedValue::forValue($this->valueRegistry->null());
		foreach($this->expressions as $expression) {
			$subRet = $expression->execute($variableValueScope);
			$result = $subRet->typedValue();

			$variableValueScope = $subRet->variableValueScope;
		}
		return new ExecutionResultValueContext($result, $variableValueScope);
	}

	public function __toString(): string {
		return count($this->expressions) > 1 ?
			sprintf("{%s}", implode("; ", $this->expressions)) :
			(string)($this->expressions[0] ?? "");
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'sequence',
			'expressions' => $this->expressions
		];
	}
}