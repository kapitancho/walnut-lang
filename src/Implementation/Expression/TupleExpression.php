<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\TupleExpression as TupleExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;

final readonly class TupleExpression implements TupleExpressionInterface {

	/** @param list<Expression> $values */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private array $values
	) {}

	/** @return list<Expression> */
	public function values(): array {
		return $this->values;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$subtypes = [];
		$returnTypes = [];
		foreach($this->values as $value) {
			$subRet = $value->analyse($variableScope);

			$subtypes[] = $subRet->expressionType;
			$variableScope = $subRet->variableScope;
			$returnTypes[] = $subRet->returnType;
		}
		return new ExecutionResultContext(
			$this->typeRegistry->tuple($subtypes),
			$this->typeRegistry->union($returnTypes),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$values = [];
		$types = [];
		foreach($this->values as $value) {
			$subRet = $value->execute($variableValueScope);

			$values[] = $subRet->value();
			$types[] = $subRet->valueType();
			$variableValueScope = $subRet->variableValueScope;
		}
		return new ExecutionResultValueContext(
			new TypedValue(
				$this->typeRegistry->tuple($types),
				$this->valueRegistry->list($values)
			),
			$variableValueScope
		);
	}

	public function __toString(): string {
		return sprintf(
			"[%s]",
			implode(", ", $this->values)
		);
	}
}