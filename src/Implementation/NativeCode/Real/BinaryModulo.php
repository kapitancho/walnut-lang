<?php

namespace Walnut\Lang\Implementation\NativeCode\Real;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\IntegerValue;

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
		if ($targetType instanceof RealType || $targetType instanceof RealSubsetType) {
			$parameterType = $this->context->toBaseType($parameterType);

			if ($parameterType instanceof IntegerType ||
				$parameterType instanceof IntegerSubsetType ||
				$parameterType instanceof RealType ||
				$parameterType instanceof RealSubsetType
			) {
				return ($parameterType->range()->minValue() === MinusInfinity::value || $parameterType->range()->minValue() < 0) &&
					($parameterType->range()->maxValue() === PlusInfinity::value || $parameterType->range()->maxValue() > 0) ?
						$this->context->typeRegistry->result(
							$this->context->typeRegistry->real(),
							$this->context->typeRegistry->atom(new TypeNameIdentifier('NotANumber'))
						) : $this->context->typeRegistry->real();
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
		if ($targetValue instanceof RealValue || $targetValue instanceof IntegerValue) {
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof IntegerValue || $parameter instanceof RealValue) {
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