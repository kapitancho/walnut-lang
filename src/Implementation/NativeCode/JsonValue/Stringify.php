<?php

namespace Walnut\Lang\Implementation\NativeCode\JsonValue;

use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Stringify implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): StringType {
		return $this->context->typeRegistry->string();
	}

	private function doStringify(Value $value): string|int|float|bool|null|array|object {
		if ($value instanceof ListValue) {
			$items = [];
			foreach($value->values() as $item) {
				$items[] = $this->doStringify($item);
			}
			return $items;
		}
		if ($value instanceof DictValue) {
			$items = [];
			foreach($value->values() as $key => $item) {
				$items[$key] = $this->doStringify($item);
			}
			return $items;
		}
		if ($value instanceof NullValue ||
			$value instanceof BooleanValue ||
			$value instanceof IntegerValue ||
			$value instanceof RealValue ||
			$value instanceof StringValue
		) {
			return $value->literalValue();
		}
		if ($value instanceof SubtypeValue) {
			return $this->doStringify($value->baseValue());
		}
		throw new ExecutionException(
			sprintf("Cannot stringify value of type %s", $value)
		);
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): TypedValue {
		return TypedValue::forValue($this->context->valueRegistry->string(
			json_encode($this->doStringify($targetValue))
		));
	}

}