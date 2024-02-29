<?php

namespace Walnut\Lang\Implementation\Function;

use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class NestedMethodRegistry implements MethodRegistry {
	/** @var MethodRegistry[] $registries */
	private array $registries;

	public function __construct(
		MethodRegistry ... $registries,
	) {
		$this->registries = $registries;
	}

	public function method(Type $targetType, MethodNameIdentifier $methodName): Method|UnknownMethod {
		foreach ($this->registries as $registry) {
			$method = $registry->method($targetType, $methodName);
			if ($method instanceof Method) {
				return $method;
			}
		}
		return UnknownMethod::value;
	}
}