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
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\IntegerValue;

final readonly class UpTo implements Method {

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
			if ($parameterType instanceof IntegerType || $parameterType instanceof IntegerSubsetType) {
				$maxLength = max(0,
					$parameterType->range()->maxValue() === PlusInfinity::value ||
					$targetType->range()->minValue() === MinusInfinity::value ? PlusInfinity::value :
					1 + $parameterType->range()->maxValue() - $targetType->range()->minValue(),
				);
				return $this->context->typeRegistry->array(
					$maxLength > 0 ? $this->context->typeRegistry->integer(
						$targetType->range()->minValue(),
						$parameterType->range()->maxValue()
					) : $this->context->typeRegistry->nothing(),
					max(0,
						$targetType->range()->maxValue() === PlusInfinity::value ||
						$parameterType->range()->minValue() === MinusInfinity::value ? 0 :
							1 + $parameterType->range()->minValue() - $targetType->range()->maxValue()
					),
					$maxLength
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
		if ($targetValue instanceof IntegerValue) {
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof IntegerValue) {
	            return $this->context->valueRegistry->list(
					$targetValue->literalValue() < $parameter->literalValue()  ?
						array_map(fn(int $i): IntegerValue =>
							$this->context->valueRegistry->integer($i),
							range($targetValue->literalValue(), $parameter->literalValue())
						) : []
	            );
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}