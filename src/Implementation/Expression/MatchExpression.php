<?php

namespace Walnut\Lang\Implementation\Expression;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\MatchExpression as MatchExpressionInterface;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;

final readonly class MatchExpression implements MatchExpressionInterface, JsonSerializable {

	/** @param list<MatchExpressionPair|MatchExpressionDefault> $pairs */
	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private Expression $target,
		private MatchExpressionOperation $operation,
		private array $pairs
	) {}

	public function target(): Expression {
		return $this->target;
	}
	public function operation(): MatchExpressionOperation {
		return $this->operation;
	}
	/** @return list<MatchExpressionPair|MatchExpressionDefault> */
	public function pairs(): array {
		return $this->pairs;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$retTarget = $this->target->analyse($variableScope);
		$variableScope = $retTarget->variableScope;

		$expressionTypes = [];
		$returnTypes = [$retTarget->returnType];
		$hasDefaultMatch = false;
		$hasDynamicTypes = false;

		$refTypes = [];

		foreach($this->pairs as $pair) {
			$innerScope = $variableScope;

			if ($pair instanceof MatchExpressionPair) {
				$matchResult = $pair->matchExpression->analyse($innerScope);
				$innerScope = $matchResult->variableScope;

				if ($this->operation instanceof MatchExpressionIsSubtypeOf && $matchResult->expressionType instanceof TypeType) {
					if ($pair->matchExpression instanceof ConstantExpression && $pair->matchExpression->value() instanceof TypeValue) {
						$refTypes[] = $pair->matchExpression->value()->typeValue();
					} else {
						$hasDynamicTypes = true;
					}
				}
				if ($this->operation instanceof MatchExpressionEquals) {
					if ($pair->matchExpression instanceof ConstantExpression) {
						$refTypes[] = $pair->matchExpression->value()->type();
					} else {
						$hasDynamicTypes = true;
					}
				}

				if ($this->target instanceof VariableNameExpression) {
					if ($this->operation instanceof MatchExpressionIsSubtypeOf && $matchResult->expressionType instanceof TypeType) {
						$innerScope = $innerScope->withAddedVariablePairs(
							new VariablePair(
								$this->target->variableName(),
								$matchResult->expressionType->refType(),
							)
						);
					}
					if ($this->operation instanceof MatchExpressionEquals) {
						$innerScope = $innerScope->withAddedVariablePairs(
							new VariablePair(
								$this->target->variableName(),
								$matchResult->expressionType,
							)
						);
					}
				}
			} else {
				$hasDefaultMatch = true;
			}
			$retValue = $pair->valueExpression->analyse($innerScope);

			$expressionTypes[] = $retValue->expressionType;
			$returnTypes[] = $retValue->returnType;
		}
		if (!$hasDefaultMatch) {
			if ($hasDynamicTypes || !$retTarget->expressionType->isSubtypeOf(
				$this->typeRegistry->union($refTypes)
			)) {
				$expressionTypes[] = $this->typeRegistry->null();
			}
		}
		return new ExecutionResultContext(
			$this->typeRegistry->union($expressionTypes),
			$this->typeRegistry->union($returnTypes),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$retTarget = $this->target->execute($variableValueScope);
		$variableValueScope = $retTarget->variableValueScope;

		foreach($this->pairs as $pair) {
			if ($pair instanceof MatchExpressionDefault) {
				return $pair->valueExpression->execute($variableValueScope);
			}
			$matchResult = $pair->matchExpression->execute($variableValueScope);
			if ($this->operation->match($retTarget->value(), $matchResult->value())) {
				$variableValueScope = $matchResult->variableValueScope;
				if ($this->target instanceof VariableNameExpression) {
					if ($this->operation instanceof MatchExpressionIsSubtypeOf && $matchResult->value() instanceof TypeValue) {
						$variableValueScope = $variableValueScope->withAddedValues(
							new VariableValuePair(
								$this->target->variableName(),
								new TypedValue(
									$matchResult->value()->typeValue(),
									$variableValueScope->valueOf($this->target->variableName())
								),
							)
						);
					}
					if ($this->operation instanceof MatchExpressionEquals) {
						$variableValueScope = $variableValueScope->withAddedValues(
							new VariableValuePair(
								$this->target->variableName(),
								$matchResult->typedValue,
							)
						);
					}
				}
				return $pair->valueExpression->execute($variableValueScope);
			}
		}
		return new ExecutionResultValueContext(
			TypedValue::forValue($this->valueRegistry->null()),
			$variableValueScope
		);
	}

	public function __toString(): string {
		$isMatchTrue = $this->target instanceof ConstantExpression &&
			$this->target->value()->equals($this->valueRegistry->true());

		$pairs = implode(", ", $this->pairs);

		return match(true) {
			$this->operation instanceof MatchExpressionIsSubtypeOf =>
				sprintf("?whenTypeOf (%s) %s { %s }", $this->target, $this->operation, $pairs),
			$isMatchTrue =>
				sprintf("?whenIsTrue { %s }", $pairs),
			default =>
				sprintf("?whenValueOf (%s) %s { %s }", $this->target, $this->operation, $pairs),
		};
	}

	public function jsonSerialize(): array {
		return [
			'expressionType' => 'Match',
			'target' => $this->target,
			'operation' => $this->operation,
			'pairs' => $this->pairs
		];
	}
}