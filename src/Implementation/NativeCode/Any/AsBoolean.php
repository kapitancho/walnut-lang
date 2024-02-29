<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\BooleanType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class AsBoolean implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): BooleanType {
		return $this->context->typeRegistry->boolean();
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): BooleanValue {
        return $this->context->valueRegistry->boolean(
            $this->evaluate($targetValue)
        );
	}

    private function evaluate(Value $value): bool {
        return match(true) {
            $value instanceof IntegerValue => $value->literalValue() !== 0,
            $value instanceof RealValue => $value->literalValue() !== 0.0,
            $value instanceof StringValue => $value->literalValue() !== '',
            $value instanceof BooleanValue => $value->literalValue(),
            $value instanceof NullValue => false,
            $value instanceof ListValue => $value->values() !== [],
            $value instanceof DictValue => $value->values() !== [],
            $value instanceof SubtypeValue => $this->evaluate($value->baseValue()),
            $value instanceof MutableValue => $this->evaluate($value->value()),
            //TODO: check for cast to boolean
            default => true
        };
    }

}