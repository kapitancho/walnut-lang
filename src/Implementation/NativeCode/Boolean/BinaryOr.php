<?php

namespace Walnut\Lang\Implementation\NativeCode\Boolean;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\BooleanType;
use Walnut\Lang\Blueprint\Type\FalseType;
use Walnut\Lang\Blueprint\Type\TrueType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class BinaryOr implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof BooleanType || $targetType instanceof TrueType || $targetType instanceof FalseType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof BooleanType || $parameterType instanceof TrueType || $parameterType instanceof FalseType) {
				return $targetType instanceof TrueType || $parameterType instanceof TrueType ?
					$this->context->typeRegistry->true() :
					$this->context->typeRegistry->boolean();
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
		if ($targetValue instanceof BooleanValue) {
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof BooleanValue) {
	            return $this->context->valueRegistry->boolean(
					$targetValue->literalValue() || $parameter->literalValue()
	            );
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}