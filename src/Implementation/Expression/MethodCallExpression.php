<?php

namespace Walnut\Lang\Implementation\Expression;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\ExecutionResultContext;
use Walnut\Lang\Blueprint\Execution\ExecutionResultValueContext;
use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableScope;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression as MethodCallExpressionInterface;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\ErrorValue;

final readonly class MethodCallExpression implements MethodCallExpressionInterface {
	public function __construct(
		private TypeRegistry $typeRegistry,
		private MethodRegistry $methodRegistry,
		private Expression $target,
		private MethodNameIdentifier $methodName,
		private Expression $parameter,
	) {}

	public function target(): Expression {
		return $this->target;
	}

	public function methodName(): MethodNameIdentifier {
		return $this->methodName;
	}

	public function parameter(): Expression {
		return $this->parameter;
	}

	private function getMethod($targetType): Method|UnknownMethod {
		return $this->methodRegistry->method($targetType,
			$this->methodName->identifier === 'as' ?
				new MethodNameIdentifier('castAs') :
				$this->methodName);
	}

	public function analyse(VariableScope $variableScope): ExecutionResultContext {
		$ret = $this->target->analyse($variableScope);
		$retExpr = $ret->expressionType;
		$variableScope = $ret->variableScope;

		//Special case: cast as - it requires the method registry and a dependency loop should be avoided.
		$method = $this->getMethod($retExpr);
		if ($method instanceof UnknownMethod) {
			throw new AnalyserException(
				sprintf(
					"Cannot call method '%s' on type '%s'",
					$this->methodName,
					$retExpr
				)
			);
		}
		$retParam = $this->parameter->analyse($variableScope);
		$retParamType = $retParam->expressionType;
		$variableScope = $retParam->variableScope;

		$retType = $method->analyse($retExpr, $retParamType, null);
		return new ExecutionResultContext(
			$retType,
			$this->typeRegistry->union([
				$ret->returnType,
				$retParam->returnType
			]),
			$variableScope
		);
	}

	public function execute(VariableValueScope $variableValueScope): ExecutionResultValueContext {
		$targetRet = $this->target->execute($variableValueScope);
		$retValue = $targetRet->value();
		$retType = $targetRet->valueType();
		$variableValueScope = $targetRet->variableValueScope;

		$method = $this->getMethod($retValue->type());
		if ($method instanceof UnknownMethod) {
			$method = $this->methodRegistry->method($retType, $this->methodName);
			if ($method instanceof UnknownMethod) {
				// @codeCoverageIgnoreStart
				throw new ExecutionException(
					sprintf(
						"Cannot call method '%s' on type '%s",
						$this->methodName,
						$retValue->type()
					)
				);
				// @codeCoverageIgnoreEnd
			}
		}

		$retParam = $this->parameter->execute($variableValueScope);
		$retParamValue = $retParam->value();
		$retParamType = $retParam->valueType();

		//$type = $method->analyse($retType, $retParamType, null);

		$value = $method->execute($retValue, $retParamValue, null);
		if ($value instanceof ErrorValue &&
			$value->errorValue()->type() instanceof StateType &&
			$value->errorValue()->type()->name() === 'DependencyContainerError'
		) {
			throw new ReturnResult($value);
		}
		if ($value instanceof Value) {
			$valueType = $value->type();
			try {
				$valueType = $method->analyse($retType, $retParamType, null);
			} catch (AnalyserException) {}
			$value = new TypedValue($valueType, $value);
		}
		return $retParam->withTypedValue($value);
	}

	public function __toString(): string {
		$parameter = (string)$this->parameter;
		if (!($parameter[0] === '[' && $parameter[-1] === ']')) {
			$parameter = "($parameter)";
		}
		if ($parameter === '(null)') {
			$parameter = '';
		}
		return sprintf(
			"%s->%s%s",
			$this->target,
			$this->methodName,
			$parameter === '(null)' ? '' : $parameter
		);
	}
}