<?php

namespace Walnut\Lang\Implementation\NativeCode\Real;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Ln implements Method {

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
			$min = $targetType->range()->minValue();
			$max = $targetType->range()->maxValue();
            $real = $this->context->typeRegistry->real(max: $max);
            return $min > 0 ? $real :
                $this->context->typeRegistry->result(
                    $real,
                    $this->context->typeRegistry->atom(
                        new TypeNameIdentifier('NotANumber')
                    )
                );
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
		if ($targetValue instanceof RealValue || $targetValue instanceof IntegerValue) {
            $val = $targetValue->literalValue();
			return $val > 0 ? $this->context->valueRegistry->real(
				log($val)
			) : $this->context->valueRegistry->error(
                $this->context->valueRegistry->atom(
                    new TypeNameIdentifier("NotANumber")
                )
            );
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}