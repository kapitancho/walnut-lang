<?php

namespace Walnut\Lang\Implementation\NativeCode\Integer;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\IntegerValue;

final readonly class BinaryBitwiseOr implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if (($targetType instanceof IntegerType || $targetType instanceof IntegerSubsetType) && $targetType->range()->minValue() >= 0) {
			$parameterType = $this->context->toBaseType($parameterType);

			if (($parameterType instanceof IntegerType || $parameterType instanceof IntegerSubsetType) && $parameterType->range()->minValue() >= 0) {
				$min = max($targetType->range()->minValue(), $parameterType->range()->minValue());
				$max = $targetType->range()->maxValue() === PlusInfinity::value ||
					$parameterType->range()->maxValue() === PlusInfinity::value ? PlusInfinity::value :
					2 * max($targetType->range()->maxValue(), $parameterType->range()->maxValue());

				return $this->context->typeRegistry->integer($min, $max);
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
		if ($targetValue instanceof IntegerValue) {
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof IntegerValue) {
	            return $this->context->valueRegistry->integer(
					$targetValue->literalValue() | $parameter->literalValue()
	            );
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