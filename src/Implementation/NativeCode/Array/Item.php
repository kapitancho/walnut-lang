<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Type\IntegerType;

final readonly class Item implements Method {

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
			if ($parameterType instanceof IntegerType || $parameterType instanceof IntegerSubsetType) {
				$returnType = $type->itemType();
				if ($targetType instanceof TupleType) {
					$min = $parameterType->range()->minValue();
					$max = $parameterType->range()->maxValue();
					if ($min !== MinusInfinity::value && $min >= 0) {
						if ($parameterType instanceof IntegerType) {
							$isWithinLimit = $max !== PlusInfinity::value && $max < count($targetType->types());
							$returnType = $this->context->typeRegistry->union(
								$isWithinLimit ?
								array_slice($targetType->types(), $min, $max - $min + 1) :
								[... array_slice($targetType->types(), $min), $targetType->restType()]
							);
						} elseif ($parameterType instanceof IntegerSubsetType) {
							$returnType = $this->context->typeRegistry->union(
								array_map(
									fn(IntegerValue $value) =>
										$targetType->types()[$value->literalValue()] ?? $targetType->restType(),
									$parameterType->subsetValues()
								)
							);
						}
					}
				}

				return $type->range()->minLength() > $parameterType->range()->maxValue() ? $returnType :
					$this->context->typeRegistry->result(
						$returnType,
						$this->context->typeRegistry->state(
							new TypeNameIdentifier("IndexOutOfRange")
						)
					);
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
		if ($targetValue instanceof ListValue && $parameter instanceof IntegerValue) {
			$values = $targetValue->values();
			$result = $values[$parameter->literalValue()] ?? null;
			return $result ?? $this->context->valueRegistry->error(
				$this->context->valueRegistry->stateValue(
					new TypeNameIdentifier('IndexOutOfRange'),
					$this->context->valueRegistry->dict(['index' => $parameter])
				)
			);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}