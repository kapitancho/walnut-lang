<?php

namespace Walnut\Lang\Implementation\NativeCode\String;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Type\TupleType;

final readonly class ConcatList implements Method {

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
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof TupleType) {
				$parameterType = $parameterType->asArrayType();
			}
			if ($parameterType instanceof ArrayType) {
				$itemType = $parameterType->itemType();
				if ($itemType instanceof StringType || $itemType instanceof StringSubsetType) {
					return $this->context->typeRegistry->string(
						$targetType->range()->minLength() +  $parameterType->range()->minLength() * $itemType->range()->minLength(),
						$targetType->range()->maxLength() === PlusInfinity::value ||
						$parameterType->range()->maxLength() === PlusInfinity::value ||
						$itemType->range()->maxLength() === PlusInfinity::value ? PlusInfinity::value :
						$targetType->range()->maxLength() +  $parameterType->range()->maxLength() * $itemType->range()->maxLength(),
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
		if ($targetValue instanceof StringValue) {
			if ($parameter instanceof ListValue) {
				$result = $targetValue->literalValue();
				foreach($parameter->values() as $value) {
					if ($value instanceof StringValue) {
						$result .= $value->literalValue();
					} else {
						// @codeCoverageIgnoreStart
						throw new ExecutionException("Invalid parameter value");
						// @codeCoverageIgnoreEnd
					}
				}
				return $this->context->valueRegistry->string($result);
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