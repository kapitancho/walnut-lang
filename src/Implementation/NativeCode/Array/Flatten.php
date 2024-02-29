<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Flatten implements Method {

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
            $itemType = $type->itemType();
            if ($itemType instanceof ArrayType) {
                return $this->context->typeRegistry->array(
                    $itemType->itemType(),
                    $type->range()->minLength() * $itemType->range()->minLength(),
                    $type->range()->maxLength() === PlusInfinity::value ||
                        $itemType->range()->maxLength() === PlusInfinity::value ?
                        PlusInfinity::value :
                        $type->range()->maxLength() * $itemType->range()->maxLength(),
                );
            }
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
			$values = $targetValue->values();
            $result = [];
            foreach($values as $value) {
                if ($value instanceof ListValue) {
                    $result = array_merge($result, $value->values());
                } else {
                    // @codeCoverageIgnoreStart
                    throw new ExecutionException("Invalid target value");
                    // @codeCoverageIgnoreEnd
                }
            }
			return $this->context->valueRegistry->list($result);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}