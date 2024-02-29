<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\FunctionType;
use Walnut\Lang\Blueprint\Type\ResultType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Map implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		$type = $targetType instanceof TupleType ? $targetType->asArrayType() : $targetType;
		if ($type instanceof ArrayType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof FunctionType) {
				if ($type->itemType()->isSubtypeOf($parameterType->parameterType())) {
					$r = $parameterType->returnType();
					$errorType = $r instanceof ResultType ? $r->errorType() : null;
					$returnType = $r instanceof ResultType ? $r->returnType() : $r;
					$t = $this->context->typeRegistry->array(
						$returnType,
						$type->range()->minLength(),
						$type->range()->maxLength(),
					);
					return $errorType ? $this->context->typeRegistry->result($t, $errorType) : $t;
				}
				throw new AnalyserException(
					"The parameter type %s of the callback function is not a subtype of %s",
					$type->itemType(),
					$parameterType->parameterType()
				);
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof ListValue && $parameter instanceof FunctionValue) {
			$values = $targetValue->values();
			$result = [];
			foreach($values as $value) {
				$r = $parameter->execute($value);
				$result[] = $r;
			}
			return $this->context->valueRegistry->list($result);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}