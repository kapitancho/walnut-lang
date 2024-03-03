<?php

namespace Walnut\Lang\Implementation\NativeCode\DependencyContainer;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\DependencyContainer;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\NativeCode\NativeCodeTypeMapper;

final readonly class ValueOf implements Method {

	/** @noinspection PhpPropertyOnlyWrittenInspection */
	public function __construct(
		private NativeCodeContext $context,
		private MethodRegistry $methodRegistry,
		private NativeCodeTypeMapper $typeMapper,
		private DependencyContainer $dependencyContainer,
	) {}

	public function analyse(
		TypeInterface $targetType,
		TypeInterface $parameterType,
		TypeInterface|null $dependencyType,
	): TypeInterface {
		if ($parameterType instanceof TypeType) {
			return $this->context->typeRegistry->result(
				$parameterType->refType(),
				$this->context->typeRegistry->withName(
					new TypeNameIdentifier('DependencyContainerError')
				)
			);
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): TypedValue {
		if ($parameter instanceof TypeValue) {
			$type = $parameter->typeValue();
			$result = $this->dependencyContainer->valueByType($type);
			if ($result instanceof Value) {
				return new TypedValue($type, $result);
			}
			return TypedValue::forValue(
				$this->context->valueRegistry->error(
					$this->context->valueRegistry->stateValue(
						new TypeNameIdentifier('DependencyContainerError'),
						$this->context->valueRegistry->dict([
							'targetType' => $this->context->valueRegistry->type($type),
							'errorMessage' => $this->context->valueRegistry->string(
								match($result) {
									UnresolvableDependency::circularDependency => 'Circular dependency',
									UnresolvableDependency::ambiguous => 'Ambiguous dependency',
									UnresolvableDependency::notFound => 'Dependency not found',
									UnresolvableDependency::unsupportedType => 'Unsupported type',
									UnresolvableDependency::errorWhileCreatingValue => 'Error returned while creating value',
								}
							)
						])
					)
				)
			);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}