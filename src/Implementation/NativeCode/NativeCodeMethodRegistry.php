<?php

namespace Walnut\Lang\Implementation\NativeCode;

use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class NativeCodeMethodRegistry implements MethodRegistry {
	private MethodRegistry $methodRegistry;
	public function __construct(
		private NativeCodeContext $context,
		private NativeCodeTypeMapper $typeMapper,
		MethodRegistry|null $methodRegistry,
		private DependencyContainer $dependencyContainer
	) {
		$this->methodRegistry = $methodRegistry ?? $this;
	}

	public function method(Type $targetType, MethodNameIdentifier $methodName): Method|UnknownMethod {
		$candidates = $this->typeMapper->getTypesFor($targetType);
		$method = ucfirst($methodName->identifier);

		foreach($candidates as $candidate) {
			$className = __NAMESPACE__ . '\\' . $candidate . '\\' . $method;
			if (class_exists($className)) {
				return new $className($this->context, $this->methodRegistry, $this->typeMapper, $this->dependencyContainer);
			}
		}
		return UnknownMethod::value;
	}
}