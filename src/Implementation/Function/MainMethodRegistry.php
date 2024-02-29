<?php

namespace Walnut\Lang\Implementation\Function;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnionType;
use Walnut\Lang\Implementation\NativeCode\NativeCodeMethodRegistry;
use Walnut\Lang\Implementation\NativeCode\NativeCodeTypeMapper;
use Walnut\Lang\Implementation\NativeCode\UnionMethodCall;
use Walnut\Lang\Implementation\Registry\CustomMethodRegistryBuilder;

final readonly class MainMethodRegistry implements MethodRegistry {

	use BaseTypeHelper;

	private NestedMethodRegistry $registry;

	public function __construct(
		private NativeCodeContext $nativeCodeContext,
		NativeCodeTypeMapper $nativeCodeTypeMapper,
		CustomMethodRegistryBuilder $customMethodRegistryBuilder,
		DependencyContainer $dependencyContainer
	) {
		$this->registry = new NestedMethodRegistry(
			$customMethodRegistryBuilder,
			new NativeCodeMethodRegistry(
				$nativeCodeContext,
				$nativeCodeTypeMapper,
				$this,
				$dependencyContainer
			)
		);
	}

	public function method(Type $targetType, MethodNameIdentifier $methodName): Method|UnknownMethod {
		$baseType = $this->toBaseType($targetType);
		if ($baseType instanceof IntersectionType) {
			$methods = [];
			foreach($baseType->types() as $type) {
				$method = $this->method($type, $methodName);
				if ($method instanceof Method) {
					$methods[] = [$type, $method];
				}
			}
			if (count($methods) > 0) {
				if (count($methods) === 1) {
					return $methods[0][1];
				}
				$method = $this->registry->method($targetType, $methodName);
				return $method instanceof Method ? $method : throw new AnalyserException(
					sprintf(
						"Cannot call method '%s' on type '%s': ambiguous method",
						$methodName,
						$targetType
					)
				);
			}
		}
		if ($baseType instanceof UnionType) {
			$methods = [];
			foreach($baseType->types() as $type) {
				$method = $this->method($type, $methodName);
				if ($method instanceof Method) {
					$methods[] = [$type, $method];
				} else {
					$methods = [];
					break;
				}
			}
			if (count($methods) > 0) {
				return new UnionMethodCall($this->nativeCodeContext, $methods);
			}
		}
		return $this->registry->method($targetType, $methodName);
	}
}