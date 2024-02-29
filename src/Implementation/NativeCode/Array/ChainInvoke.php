<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\FunctionType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ChainInvoke implements Method {

    public function __construct(
        private NativeCodeContext $context
    ) {
    }

    public function analyse(
        Type      $targetType,
        Type      $parameterType,
        Type|null $dependencyType,
    ): Type {
        $targetType = $this->context->toBaseType($targetType);
        $type = $targetType instanceof TupleType ? $targetType->asArrayType() : $targetType;
        if ($type instanceof ArrayType) {
            $itemType = $this->context->toBaseType($type->itemType());
            if ($itemType instanceof FunctionType) {
                if ($itemType->returnType()->isSubtypeOf($itemType->parameterType())) {
                    if ($parameterType->isSubtypeOf($itemType->parameterType())) {
                        return $itemType->returnType();
                    }
                    throw new AnalyserException(
                        "The parameter type %s is not a subtype of %s",
                        $parameterType,
                        $itemType->parameterType()
                    );
                }
            }
        }
        throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
    }

    public function execute(
        Value $targetValue,
        Value $parameter,
        Value|null $dependencyValue,
    ): Value {
        $targetValue = $this->context->toBaseValue($targetValue);
        if ($targetValue instanceof ListValue) {
            foreach($targetValue->values() as $fnValue) {
                $parameter = $fnValue->execute($parameter);
            }
        }
        return $parameter;
    }

}

