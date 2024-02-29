<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Sum implements Method {

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
			if ($itemType->isSubtypeOf(
				$this->context->typeRegistry->union([
					$this->context->typeRegistry->integer(),
					$this->context->typeRegistry->real()
				])
			)) {
				if ($itemType instanceof RealType || $itemType instanceof RealSubsetType) {
					return $this->context->typeRegistry->real(
                        $itemType->range()->minValue() === MinusInfinity::value ? MinusInfinity::value :
                            $itemType->range()->minValue() * $targetType->range()->minLength(),
                        $itemType->range()->maxValue() === PlusInfinity::value ||
                        $targetType->range()->maxLength() === PlusInfinity::value ? PlusInfinity::value :
                            $itemType->range()->maxValue() * $targetType->range()->maxLength()
					);
				}
				if ($itemType instanceof IntegerType || $itemType instanceof IntegerSubsetType) {
					return $this->context->typeRegistry->integer(
                        $itemType->range()->minValue() === MinusInfinity::value ? MinusInfinity::value :
						    $itemType->range()->minValue() * $targetType->range()->minLength(),
                        $itemType->range()->maxValue() === PlusInfinity::value ||
                        $targetType->range()->maxLength() === PlusInfinity::value ? PlusInfinity::value :
						    $itemType->range()->maxValue() * $targetType->range()->maxLength()
					);
				}
				return $this->context->typeRegistry->real();
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
		if ($targetValue instanceof ListValue && count($targetValue->values()) > 0) {
			$sum = 0;
			$hasReal = false;
			foreach($targetValue->values() as $item) {
				$v = $item->literalValue();
				if (is_float($v)) {
					$hasReal = true;
				}
				$sum += $v;
			}
			return $hasReal ? $this->context->valueRegistry->real($sum) : $this->context->valueRegistry->integer($sum);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}