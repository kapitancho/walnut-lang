<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\ConstantExpression;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\VariableAssignmentExpression as VariableAssignmentExpressionInterface;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Value\FunctionBodyException;
use Walnut\Lang\Blueprint\Value\FunctionValue;

final readonly class VariableAssignmentExpression implements VariableAssignmentExpressionInterface, JsonSerializable {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private VariableNameIdentifier $variableName,
		private Expression $assignedExpression
	) {}

	public function variableName(): VariableNameIdentifier {
		return $this->variableName;
	}

	public function assignedExpression(): Expression {
		return $this->assignedExpression;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$innerFn = null;
		if ($this->assignedExpression instanceof ConstantExpression &&
			($v = $this->assignedExpression->value()) instanceof FunctionValue
		) {
			$innerFn = $this->variableName;
			$variableScope = $variableScope->withAddedVariablePairs(
				new VariablePair(
					$this->variableName,
					$this->typeRegistry->function(
						$v->parameterType(),
						$v->returnType(),
					)
				)
			);
		}
		try {
			$ret = $this->assignedExpression->analyse($variableScope);
			return $ret->withVariableScope(
				$ret->variableScope->withAddedVariablePairs(
					new VariablePair(
						$this->variableName,
						$ret->expressionType
					)
				)
			);
		} catch (AnalyserException|FunctionBodyException $e) {
			throw $innerFn ? new AnalyserException(
				sprintf(
					"Error in function assigned to variable '%s': %s",
					$this->variableName,
					$e->getMessage()
				)
			) : $e;
		}
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$ret = $this->assignedExpression->execute($variableValueScope);
		$val = $ret->typedValue();
		if ($val->value instanceof FunctionValue && $this->assignedExpression instanceof ConstantExpression) {
			$val = new TypedValue($val->type, $val->value->withSelfReferenceAs($this->variableName));
		}
		return $ret->withVariableValueScope(
			$ret->variableValueScope->withAddedValues(
				new VariableValuePair(
					$this->variableName,
					$val
				)
			)
		);
	}

	public function __toString(): string {
		return sprintf(
			"%s = %s",
			$this->variableName,
			$this->assignedExpression
		);
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'variableAssignment',
			'variableName' => $this->variableName,
			'assignedExpression' => $this->assignedExpression
		];
	}
}