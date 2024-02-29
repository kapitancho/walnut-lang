<?php

namespace Walnut\Lang\Implementation\NativeCode\Integer;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\IntegerValue;

final readonly class BinaryMinus implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof IntegerType || $targetType instanceof IntegerSubsetType) {
			$parameterType = $this->context->toBaseType($parameterType);

			if ($parameterType instanceof IntegerType ||
				$parameterType instanceof IntegerSubsetType ||
				$parameterType instanceof RealType ||
				$parameterType instanceof RealSubsetType
			) {
				$min = $targetType->range()->minValue() === MinusInfinity::value ||
					$parameterType->range()->maxValue() === PlusInfinity::value ? MinusInfinity::value :
					$targetType->range()->minValue() - $parameterType->range()->maxValue();
				$max = $targetType->range()->maxValue() === PlusInfinity::value ||
					$parameterType->range()->minValue() === MinusInfinity::value ? PlusInfinity::value :
					$targetType->range()->maxValue() - $parameterType->range()->minValue();

				if ($parameterType instanceof IntegerType || $parameterType instanceof IntegerSubsetType) {
					return $this->context->typeRegistry->integer($min, $max);
				}
				if ($parameterType instanceof RealType || $parameterType instanceof RealSubsetType) {
					return $this->context->typeRegistry->real($min, $max);
				}
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
					$targetValue->literalValue() - $parameter->literalValue()
	            );
			}
			if ($parameter instanceof RealValue) {
	            return $this->context->valueRegistry->real(
					$targetValue->literalValue() - $parameter->literalValue()
	            );
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}