<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Type\TupleType;

final readonly class CombineAsString implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof TupleType) {
			$targetType = $targetType->asArrayType();
		}
		if ($targetType instanceof ArrayType) {
			$itemType = $targetType->itemType();
			if ($itemType->isSubtypeOf($this->context->typeRegistry->string())) {
				if ($parameterType instanceof StringType || $parameterType instanceof StringSubsetType) {
					return $this->context->typeRegistry->string(
						/*$parameterType->range()->minLength() * max(0, $targetType->range()->minLength() - 1) +
						$itemType->range()->minLength() * $targetType->range()->minLength(),
						$parameterType->range()->maxLength() === PlusInfinity::value ||
						$itemType->range()->maxLength() === PlusInfinity::value ||
						$targetType->range()->maxLength() === PlusInfinity::value ? PlusInfinity::value :
							$parameterType->range()->maxLength() * max(0, $targetType->range()->maxLength() - 1) +
								$itemType->range()->maxLength() * $targetType->range()->maxLength()*/
					);
				}
				// @codeCoverageIgnoreStart
				throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
				// @codeCoverageIgnoreEnd
			}
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
		$parameter = $this->context->toBaseValue($parameter);
		if ($targetValue instanceof ListValue) {
			if ($parameter instanceof StringValue) {
				$result = [];
				foreach($targetValue->values() as $value) {
					if ($value instanceof StringValue) {
						$result[] = $value->literalValue();
					} else {
						// @codeCoverageIgnoreStart
						throw new ExecutionException("Invalid parameter value");
						// @codeCoverageIgnoreEnd
					}
				}
				$result = implode($parameter->literalValue(), $result);
				return $this->context->valueRegistry->string($result);
			}
			// @codeCoverageIgnoreStart
			throw new ExecutionException("Invalid parameter value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}