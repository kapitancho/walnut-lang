<?php

namespace Walnut\Lang\Implementation\NativeCode\Random;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\AtomValue;

final readonly class Integer implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof AtomType && $targetType->name()->equals(new TypeNameIdentifier('Random'))) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof RecordType) {
				$fromType = $parameterType->types()['min'] ?? null;
				$toType = $parameterType->types()['max'] ?? null;

				if (
					($fromType instanceof IntegerType || $fromType instanceof IntegerSubsetType) &&
					($toType instanceof IntegerType || $toType instanceof IntegerSubsetType)
				) {
					$fromMax = $fromType->range()->maxValue();
					$toMin = $toType->range()->minValue();
					if ($fromMax === PlusInfinity::value || $toMin === MinusInfinity::value || $fromMax > $toMin) {
						// @codeCoverageIgnoreStart
						throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s - range is not compatible", __CLASS__, $parameterType));
						// @codeCoverageIgnoreEnd
					}
					return $this->context->typeRegistry->integer(
						$fromType->range()->minValue(),
						$toType->range()->maxValue()
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
		$parameter = $this->context->toBaseValue($parameter);
		if ($targetValue instanceof AtomValue && $targetValue->type()->name()->equals(
			new TypeNameIdentifier('Random')
		)) {
			if ($parameter instanceof DictValue) {
				$from = $parameter->valueOf(new PropertyNameIdentifier('min'));
				$to = $parameter->valueOf(new PropertyNameIdentifier('max'));
				if (
					$from instanceof IntegerValue &&
					$to instanceof IntegerValue
				) {
					return $this->context->valueRegistry->integer(random_int($from->literalValue(), $to->literalValue()));
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