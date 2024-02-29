<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Slice implements Method {

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
			$pInt = $this->context->typeRegistry->integer(0);
			$pType = $this->context->typeRegistry->record([
				"start" => $pInt,
				"length" => $pInt
			]);
			if ($parameterType->isSubtypeOf($pType)) {
				$parameterType = $this->context->toBaseType($parameterType);
				return $this->context->typeRegistry->array(
					$targetType->itemType(),
					0,
					min(
						$targetType->range()->maxLength,
						$parameterType->types()['length']->range()->maxValue
					)
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
		if ($targetValue instanceof ListValue) {
			if ($parameter instanceof DictValue) {
				$start = $parameter->valueOf(new PropertyNameIdentifier('start'));
				$length = $parameter->valueOf(new PropertyNameIdentifier('length'));
				if (
					$start instanceof IntegerValue &&
					$length instanceof IntegerValue
				) {
					$values = $targetValue->values();
					$values = array_slice(
						$values,
						$start->literalValue(),
						$length->literalValue()
					);
					return $this->context->valueRegistry->list($values);
				}
				// @codeCoverageIgnoreStart
				throw new ExecutionException("Invalid parameter value");
				// @codeCoverageIgnoreEnd
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}