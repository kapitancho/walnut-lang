<?php

namespace Walnut\Lang\Implementation\NativeCode\Integer;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class BinaryModulo implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof IntegerType || $targetType instanceof IntegerSubsetType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof IntegerType || $parameterType instanceof IntegerSubsetType) {
				return $this->context->typeRegistry->integer();
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
		if ($targetValue instanceof IntegerValue) {
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof IntegerValue) {
				if ($parameter->literalValue() === 0) {
					return $this->context->valueRegistry->error(
						$this->context->valueRegistry->atom(new TypeNameIdentifier('NotANumber'))
					);
				}
                return $this->context->valueRegistry->integer(
	                $targetValue->literalValue() % $parameter->literalValue()
                );
			}
			if ($parameter instanceof RealValue) {
				if ((float)$parameter->literalValue() === 0.0) {
					return $this->context->valueRegistry->error(
						$this->context->valueRegistry->atom(new TypeNameIdentifier('NotANumber'))
					);
				}
                return $this->context->valueRegistry->real(
	                fmod($targetValue->literalValue(), $parameter->literalValue())
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