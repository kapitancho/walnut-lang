<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\ReturnExpression as ReturnExpressionInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;

final readonly class ReturnExpression implements ReturnExpressionInterface, JsonSerializable {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private Expression $returnedExpression
	) {}

	public function returnedExpression(): Expression {
		return $this->returnedExpression;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$ret = $this->returnedExpression->analyse($variableScope);
		return $ret->withExpressionType(
			$this->typeRegistry->nothing()
		)->withReturnType($this->typeRegistry->union(
			[$ret->returnType, $ret->expressionType]
		));
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		throw new ReturnResult(
			$this->returnedExpression->execute($variableValueScope)->value()
		);
	}

	public function __toString(): string {
		return sprintf(
			"=> %s",
			$this->returnedExpression
		);
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'return',
			'returnedExpression' => $this->returnedExpression
		];
	}
}