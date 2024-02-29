<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Max implements Method {

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
		if ($targetType instanceof ArrayType && $targetType->range()->minLength() > 0) {
			$itemType = $targetType->itemType();
			if ($itemType->isSubtypeOf(
				$this->context->typeRegistry->union([
					$this->context->typeRegistry->integer(),
					$this->context->typeRegistry->real()
				])
			)) {
				if ($itemType instanceof RealType || $itemType instanceof RealSubsetType) {
					return $this->context->typeRegistry->real(
						$itemType->range()->minValue(),
						$itemType->range()->maxValue()
					);
				}
				if ($itemType instanceof IntegerType || $itemType instanceof IntegerSubsetType) {
					return $this->context->typeRegistry->integer(
						$itemType->range()->minValue(),
						$itemType->range()->maxValue()
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
			$maxV = $targetValue->values()[0];
			$max = $maxV->literalValue();
			foreach($targetValue->values() as $item) {
				$value = $item->literalValue();
				if ($value > $max) {
					$maxV = $item;
					$max = $value;
				}
			}
			return $maxV;
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}