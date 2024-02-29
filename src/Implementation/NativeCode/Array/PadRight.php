<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class PadRight implements Method {
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
			if ($parameterType instanceof RecordType) {
				$types = $parameterType->types();
				$lengthType = $types['length'] ?? null;
				$valueType = $types['value'] ?? null;
				if ($lengthType instanceof IntegerType || $lengthType instanceof IntegerSubsetType) {
					return $this->context->typeRegistry->array(
						$this->context->typeRegistry->union([
							$type->itemType(),
							$valueType
						]),
						max($type->range()->minLength(), $lengthType->range()->minValue()),
						$type->range()->maxLength() === PlusInfinity::value ||
							$lengthType->range()->maxValue() === PlusInfinity::value ? PlusInfinity::value :
							max($type->range()->maxLength(), $lengthType->range()->maxValue()),
					);
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
		if ($targetValue instanceof ListValue) {
			if ($parameter instanceof DictValue) {
				$values = $targetValue->values();

				$paramValues = $parameter->values();
				$length = $paramValues['length'] ?? null;
				$padValue = $paramValues['value'] ?? null;
				if ($length instanceof IntegerValue && $padValue !== null) {
					$result = array_pad(
						$values,
						$length->literalValue(),
						$padValue
					);
					return $this->context->valueRegistry->list($result);
				}
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