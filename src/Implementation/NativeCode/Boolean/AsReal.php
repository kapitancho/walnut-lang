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

final readonly class AsReal implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof BooleanType) {
			return $this->context->typeRegistry->realSubset([
				$this->context->valueRegistry->real(0.0),
				$this->context->valueRegistry->real(1.0)
			]);
		}
		if ($targetType instanceof TrueType) {
			return $this->context->typeRegistry->realSubset([
				$this->context->valueRegistry->real(1.0)
			]);
		}
		if ($targetType instanceof FalseType) {
			return $this->context->typeRegistry->realSubset([
				$this->context->valueRegistry->real(0.0)
			]);
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
			$target = $targetValue->literalValue();
			return $this->context->valueRegistry->real($target ? 1.0 : 0.0);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}