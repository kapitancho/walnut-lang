<?php

namespace Walnut\Lang\Implementation\NativeCode\Type;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\StateType as StateTypeInterface;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class StateType implements Method {

	use BaseTypeHelper;

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		TypeInterface $targetType,
		TypeInterface $parameterType,
		TypeInterface|null $dependencyType,
	): TypeInterface {
		if ($targetType instanceof TypeType) {
			$refType = $targetType->refType();
			if ($refType instanceof StateTypeInterface) {
				return $this->context->typeRegistry->type($refType->stateType());
			}
			if ($refType instanceof MetaType) {
				if ($refType->value() === MetaTypeValue::State) {
					return $this->context->typeRegistry->type($this->context->typeRegistry->any());
				}
			}
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $parameterType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		if ($targetValue instanceof TypeValue) {
			$typeValue = $targetValue->typeValue();
			if ($typeValue instanceof StateTypeInterface) {
				return $this->context->valueRegistry->type($typeValue->stateType());
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}