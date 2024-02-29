<?php

namespace Walnut\Lang\Implementation\NativeCode\String;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\IntegerValue;

final readonly class SubstringRange implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof StringType || $targetType instanceof StringSubsetType) {
			$pInt = $this->context->typeRegistry->integer(0);
			$pType = $this->context->typeRegistry->record([
				"start" => $pInt,
				"end" => $pInt
			]);
			if ($parameterType->isSubtypeOf($pType)) {
				$parameterType = $this->context->toBaseType($parameterType);
				$endType = $parameterType->types()['end'];
				return $this->context->typeRegistry->string(0,
					$endType->range()->maxValue() === PlusInfinity::value ? PlusInfinity::value :
					min($targetType->range()->maxLength,
					$endType->range()->maxValue - $parameterType->types()['start']->range()->minValue()
				));
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
		if (
			$targetValue instanceof StringValue &&
			$parameter instanceof DictValue
		) {
			$start = $parameter->valueOf(new PropertyNameIdentifier('start'));
			$end = $parameter->valueOf(new PropertyNameIdentifier('end'));
			if (
				$start instanceof IntegerValue &&
				$end instanceof IntegerValue
			) {
				$length = $end->literalValue() - $start->literalValue();
				return $this->context->valueRegistry->string(
					mb_substr(
						$targetValue->literalValue(),
						$start->literalValue(),
						$length
					)
				);
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