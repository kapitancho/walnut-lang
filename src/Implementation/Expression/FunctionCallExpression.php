<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\FunctionCallExpression as FunctionCallExpressionInterface;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\FunctionType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\ListValue;

final readonly class FunctionCallExpression implements FunctionCallExpressionInterface {
	use BaseTypeHelper;

	public function __construct(
		private TypeRegistry $typeRegistry,
		private ValueRegistry $valueRegistry,
		private MethodRegistry $methodRegistry,
		private Expression $target,
		private Expression $parameter,
	) {}

	public function target(): Expression {
		return $this->target;
	}

	public function parameter(): Expression {
		return $this->parameter;
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$ret = $this->target->analyse($variableScope);
		$retExpr = $this->toBaseType($ret->expressionType);
		$variableScope = $ret->variableScope;

		if ($retExpr instanceof FunctionType) {
			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $retParam->expressionType;
			$variableScope = $retParam->variableScope;

			if (!(
				$retParamType->isSubtypeOf($retExpr->parameterType()) || (
					$retExpr->parameterType() instanceof RecordType &&
					$retParamType instanceof TupleType &&
					$this->isTupleCompatibleToRecord(
						$this->typeRegistry, $retParamType, $retExpr->parameterType()
					)
				)
			)) {
				throw new AnalyserException(
					sprintf(
						"Cannot pass a parameter of type %s to a function expecting a parameter of type %s",
						$retParamType,
						$retExpr->parameterType(),
					)
				);
			}
			return new ExecutionResultContext(
				$retExpr->returnType(),
				$this->typeRegistry->union([
					$ret->returnType,
					$retParam->returnType
				]),
				$variableScope
			);
		}
		$method = $this->methodRegistry->method($ret->expressionType, new MethodNameIdentifier('invoke'));
		if ($method instanceof Method) {
			$retParam = $this->parameter->analyse($variableScope);
			$retParamType = $retParam->expressionType;
			$variableScope = $retParam->variableScope;

			$methodReturnType = $method->analyse($ret->expressionType, $retParamType, null);
			return new ExecutionResultContext(
				$methodReturnType,
				$this->typeRegistry->union([
					$ret->returnType,
					$retParam->returnType
				]),
				$variableScope
			);
		}
		throw new AnalyserException(sprintf("Cannot call a non-function value %s", $this->target));
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$targetRet = $this->target->execute($variableValueScope);
		$retValue = $this->toBaseValue($targetRet->value());
		$variableValueScope = $targetRet->variableValueScope;

		if ($retValue instanceof FunctionValue) {
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			if ($retParamValue instanceof ListValue && $retValue->parameterType() instanceof RecordType) {
				$retParamValue = $this->getTupleAsRecord(
					$this->valueRegistry,
					$retParamValue,
					$retValue->parameterType(),
				);
			}

			$value = $retValue->execute($retParamValue);
			$type = $retParam->valueType();
			$valueType = match(true) {
				$type instanceof FunctionType => $type->returnType(),
				default => $value->type()
			};
			return $retParam->withTypedValue(new TypedValue($valueType, $value));
		}
		$method = $this->methodRegistry->method($retValue->type(), new MethodNameIdentifier('invoke'));
		if ($method instanceof UnknownMethod) {
			$method = $this->methodRegistry->method($targetRet->valueType(), new MethodNameIdentifier('invoke'));
		}
		if ($method instanceof Method) {
			$retParam = $this->parameter->execute($variableValueScope);
			$retParamValue = $retParam->value();

			$result = $method->execute($retValue, $retParamValue, null);
			return $retParam->withTypedValue(
				$result instanceof TypedValue ? $result : TypedValue::forValue($result)
			);
		}
		throw new ExecutionException(
			sprintf("Cannot call a non-function value %s", $retValue)
		);
	}

	public function __toString(): string {
		$parameter = (string)$this->parameter;
		if (!($parameter[0] === '[' && $parameter[-1] === ']')) {
			$parameter = "($parameter)";
		}
		if ($parameter === '(null)') {
			$parameter = '()';
		}
		return sprintf(
			"%s%s",
			$this->target,
			$parameter
		);
	}
}