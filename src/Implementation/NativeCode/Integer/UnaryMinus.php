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
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class UnaryMinus implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof IntegerSubsetType) {
			return $this->context->typeRegistry->integerSubset(
				array_map(fn(IntegerValue $value): IntegerValue =>
					$this->context->valueRegistry->integer(-$value->literalValue()),
					$targetType->subsetValues()
				)
			);
		}
		if ($targetType instanceof IntegerType) {
			return $this->context->typeRegistry->integer(
				$targetType->range()->maxValue() === PlusInfinity::value ? MinusInfinity::value :
					-$targetType->range()->maxValue(),
				$targetType->range()->minValue() === MinusInfinity::value ? PlusInfinity::value :
					-$targetType->range()->minValue()
			);
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
			$target = $targetValue->literalValue();
			return $this->context->valueRegistry->integer(-$target);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}