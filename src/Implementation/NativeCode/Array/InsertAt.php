<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class InsertAt implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof ArrayType) {
			$pInt = $this->context->typeRegistry->integer(0);
			$pType = $this->context->typeRegistry->record([
				"value" => $this->context->typeRegistry->any(),
				"index" => $pInt
			]);
			if ($parameterType->isSubtypeOf($pType)) {
				$parameterType = $this->context->toBaseType($parameterType);
				$returnType = $this->context->typeRegistry->array(
					$this->context->typeRegistry->union([
						$targetType->itemType(),
						$parameterType->types()['value']
					]),
					$targetType->range()->minLength() + 1,
					$targetType->range()->maxLength() === PlusInfinity::value ?
						PlusInfinity::value : $targetType->range()->maxLength() + 1
				);
				return
					$parameterType->types()['index']->range()->maxValue() >= 0 &&
					$parameterType->types()['index']->range()->maxValue() <= $targetType->range()->minLength() ?
					$returnType : $this->context->typeRegistry->result($returnType,
						$this->context->typeRegistry->state(new TypeNameIdentifier('IndexOutOfRange'))
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
				$value = $parameter->valueOf(new PropertyNameIdentifier('value'));
				$index = $parameter->valueOf(new PropertyNameIdentifier('index'));
				if ($index instanceof IntegerValue) {
					$idx = $index->literalValue();
					$values = $targetValue->values();
					if ($idx >= 0 && $idx <= count($values)) {
						array_splice(
							$values,
							$idx,
							0,
							[$value]
						);
						return $this->context->valueRegistry->list($values);
					}
				}
				// @codeCoverageIgnoreStart
				throw new ExecutionException("Invalid parameter value");
				// @codeCoverageIgnoreEnd
			}
			$values = $targetValue->values();
			$values[] = $parameter;
			return $this->context->valueRegistry->list($values);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}