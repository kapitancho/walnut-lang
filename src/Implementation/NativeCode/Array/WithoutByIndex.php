<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class WithoutByIndex implements Method {

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
				$returnType = $this->context->typeRegistry->record([
					'element' => $type->itemType(),
					'array' => $this->context->typeRegistry->array(
						$type->itemType(),
						max(0, $type->range()->minLength() - 1),
						$type->range()->maxLength() === PlusInfinity::value ?
							PlusInfinity::value : $type->range()->maxLength() - 1
					)
				]);
				return $parameterType->range()->minValue() < $type->range()->maxLength() ? $returnType :
					$this->context->typeRegistry->result(
						$returnType,
						$this->context->typeRegistry->state(
							new TypeNameIdentifier("IndexOutOfRange")
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
			if ($parameter instanceof IntegerValue) {
				$values = $targetValue->values();
				$p = $parameter->literalValue();
				if (!array_key_exists($p, $values)) {
					return $this->context->valueRegistry->stateValue(
						new TypeNameIdentifier('IndexOutOfRange'),
						$this->context->valueRegistry->dict(['index' => $parameter])
					);
				}
				$removed = array_splice($values, $p, 1);
				return $this->context->valueRegistry->dict([
					'element' => $removed[0],
					'array' => $this->context->valueRegistry->list($values)
				]);
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