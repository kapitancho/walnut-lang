<?php

namespace Walnut\Lang\Implementation\NativeCode\JsonValue;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\NativeCode\HydrationException;
use Walnut\Lang\Implementation\NativeCode\Hydrator;

final readonly class HydrateAs implements Method {

	public function __construct(
		private NativeCodeContext $context,
		private MethodRegistry $methodRegistry,
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($parameterType instanceof TypeType) {
			return $this->context->typeRegistry->result(
				$parameterType->refType(),
				$this->context->typeRegistry->withName(new TypeNameIdentifier("HydrationError"))
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
	): Value {
		if ($parameter instanceof TypeValue) {
			try {
				return (new Hydrator(
					$this->context,
					$this->methodRegistry,
				))->hydrate($targetValue, $parameter->typeValue(), 'value');
			} catch (HydrationException $e) {
				return $this->context->valueRegistry->error(
					$this->context->valueRegistry->stateValue(
						new TypeNameIdentifier("HydrationError"),
						$this->context->valueRegistry->dict([
							'value' => $e->value,
							'hydrationPath' => $this->context->valueRegistry->string($e->hydrationPath),
							'errorMessage' => $this->context->valueRegistry->string($e->errorMessage),
						])
					)
				);
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}