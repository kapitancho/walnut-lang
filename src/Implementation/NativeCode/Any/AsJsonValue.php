<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\EnumerationValue;

final readonly class AsJsonValue implements Method {

	public function __construct(
		private NativeCodeContext $context,
		private MethodRegistry $methodRegistry,
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$resultType = $this->context->typeRegistry->alias(new TypeNameIdentifier('JsonValue'));
		return $this->isSafeConversion($targetType) ? $resultType : $this->context->typeRegistry->result(
			$resultType,
			$this->context->typeRegistry->withName(new TypeNameIdentifier('InvalidJsonValue'))
		);
	}

	private function isSafeConversion(Type $fromType): bool {
		return $fromType->isSubtypeOf(
			$this->context->typeRegistry->withName(new TypeNameIdentifier('JsonValue'))
		);
	}

	private function asJsonValue(Value $value): Value|TypedValue {
		if ($value instanceof ListValue) {
			$items = [];
			foreach($value->values() as $item) {
				$items[] = $this->asJsonValue($item);
			}
			return $this->context->valueRegistry->list($items);
		}
		if ($value instanceof DictValue) {
			$items = [];
			foreach($value->values() as $key => $item) {
				$items[$key] = $this->asJsonValue($item);
			}
			return $this->context->valueRegistry->dict($items);
		}
		if ($value instanceof NullValue ||
			$value instanceof BooleanValue ||
			$value instanceof IntegerValue ||
			$value instanceof RealValue ||
			$value instanceof StringValue
		) {
			return $value;
		}
		$method = $this->methodRegistry->method(
			$value->type(),
			new MethodNameIdentifier('asJsonValue')
		);
		if ($method instanceof Method && !($method instanceof self)) {
			return $method->execute($value, $this->context->valueRegistry->null(), null);
		}
		if ($value instanceof SubtypeValue) {
			return $this->asJsonValue($value->baseValue());
		}
		if ($value instanceof StateValue) {
			return $this->asJsonValue($value->stateValue());
		}
		if ($value instanceof EnumerationValue) {
			return $this->context->valueRegistry->string($value->name()->identifier);
		}
		throw new ReturnResult($this->context->valueRegistry->error(
			$this->context->valueRegistry->stateValue(
				new TypeNameIdentifier('InvalidJsonValue'),
				$this->context->valueRegistry->dict(['value' => $value])
			)
		));
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): TypedValue {
        $result = $this->asJsonValue($targetValue);
		return $result instanceof Value ? new TypedValue(
			$this->context->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
			$result,
		) : $result;
	}

}