<?php

namespace Walnut\Lang\Implementation\Registry;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Function\CustomMethod as CustomMethodInterface;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Function\UnknownMethod;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\CustomMethodRegistryBuilder as CustomMethodRegistryBuilderInterface;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Implementation\Function\CustomMethod;

final class CustomMethodRegistryBuilder implements MethodRegistry, CustomMethodRegistryBuilderInterface {

	/**
	 * @var array<string, list<CustomMethodInterface>> $methods
	 */
	private array $methods;

	public function __construct(
		private readonly TypeRegistry             $typeRegistry,
		private readonly ValueRegistry            $valueRegistry,
		private readonly DependencyContainer $dependencyContainer,
	) {
		$this->methods = [];
	}

	public function addMethod(
		Type $targetType,
		MethodNameIdentifier $methodName,
		Type $parameterType,
		Type|null $dependencyType,
		Type $returnType,
		FunctionBody $functionBody,
	): CustomMethodInterface {
		$this->methods[$methodName->identifier] ??= [];
		$this->methods[$methodName->identifier][] = $method = new CustomMethod(
			$this->typeRegistry,
			$this->valueRegistry,
			$this->dependencyContainer,
			$targetType,
			$methodName,
			$parameterType,
			$dependencyType,
			$returnType,
			$functionBody,
		);
		return $method;
	}

	public function method(Type $targetType, MethodNameIdentifier $methodName): Method|UnknownMethod {
		foreach($this->methods[$methodName->identifier] ?? [] as $method) {
			if ($targetType->isSubtypeOf($method->targetType())) {
				return $method;
			}
		}
		return UnknownMethod::value;
	}

	private function getErrorMessageFor(CustomMethodInterface $method): string {
		return match(true) {
			(string)$method->targetType() === 'Constructor'
				=> sprintf("Error in the constructor of %s", $method->methodName()),
			(string)$method->targetType() === 'DependencyContainer'
				=> sprintf("Error in the dependency builder of %s", substr($method->methodName(), 2)),
			str_starts_with($method->methodName()->identifier, 'as')
				=> sprintf("Error in the cast %s ==> %s", $method->targetType(),
					substr($method->methodName(), 2)),
			default => sprintf("Error in method %s->%s", $method->targetType(), $method->methodName())
		};
	}

	public function analyse(): void {
		foreach($this->methods as $methods) {
			foreach($methods as $method) {
				try {
					$method->analyse(
						$method->targetType(),
						$method->parameterType(),
						$method->dependencyType()
					);
				} catch (AnalyserException $e) {
					throw new AnalyserException(
						sprintf("%s : %s",$this->getErrorMessageFor($method), $e->getMessage())
					);
				}
				if ($method->dependencyType()) {
					$value = $this->dependencyContainer->valueByType($method->dependencyType());
					if ($value instanceof UnresolvableDependency) {
						throw new AnalyserException(
							sprintf("%s : the dependency %s cannot be resolved: %s",
								$this->getErrorMessageFor($method),
								$method->dependencyType(),
								match($value) {
									UnresolvableDependency::notFound => "no appropriate value found",
									UnresolvableDependency::ambiguous => "ambiguity - multiple values found",
									UnresolvableDependency::circularDependency => "circular dependency detected",
									UnresolvableDependency::unsupportedType => "unsupported type found"
								}
							)
						);
					}
				}
			}
		}
	}
}