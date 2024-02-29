<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\NoErrorExpression as NoErrorExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\ResultType;
use Walnut\Lang\Blueprint\Value\ErrorValue;

final readonly class NoErrorExpression implements NoErrorExpressionInterface {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private Expression $targetExpression
	) {}

	public function targetExpression(): Expression {
		return $this->targetExpression;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$ret = $this->targetExpression->analyse($variableScope);
		if ($ret->expressionType instanceof ResultType) {
			return $ret->withExpressionType(
				$ret->expressionType->returnType()
			)->withReturnType(
				$this->typeRegistry->result(
					$ret->returnType,
					$ret->expressionType->errorType()
				)
			);
		}
		return $ret;
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$result = $this->targetExpression->execute($variableValueScope);
		if ($result->value() instanceof ErrorValue) {
			throw new ReturnResult(
				$this->targetExpression->execute($variableValueScope)->value()
			);
		}
		$vt = $result->valueType();
		if ($vt instanceof ResultType) {
			$result = $result->withTypedValue(new TypedValue($vt->returnType(), $result->value()));
		}
		return $result;
	}

	public function __toString(): string {
		return sprintf(
			"?noError(%s)",
			$this->targetExpression
		);
	}
}