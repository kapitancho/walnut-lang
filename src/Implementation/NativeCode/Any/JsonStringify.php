<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class JsonStringify implements Method {

	public function __construct(
		private NativeCodeContext $context,
		private MethodRegistry $methodRegistry,
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$resultType = $this->context->typeRegistry->string();
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

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): TypedValue {
		$method1 = $this->methodRegistry->method(
			$targetValue->type(), new MethodNameIdentifier('asJsonValue')
		);
		$step1 = $method1->execute($targetValue, $parameter, $dependencyValue);
		$method2 = $this->methodRegistry->method(
			$this->context->typeRegistry->alias(new TypeNameIdentifier('JsonValue')),
			new MethodNameIdentifier('stringify')
		);
		return $method2->execute($step1->value, $parameter, $dependencyValue);
	}

}