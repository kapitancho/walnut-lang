<?php

namespace Walnut\Lang\Implementation\NativeCode\Map;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class WithKeyValue implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof RecordType) {
			$targetType = $targetType->asMapType();
		}
		if ($targetType instanceof MapType) {
			if ($parameterType->isSubtypeOf(
				$this->context->typeRegistry->record([
					'key' => $this->context->typeRegistry->string(),
					'value' => $this->context->typeRegistry->any()
				])
			)) {
				$valueType = $parameterType->types()['value'] ?? null;
				return $this->context->typeRegistry->map(
					$this->context->typeRegistry->union([
						$targetType->itemType(),
						$valueType
					]),
					$targetType->range()->minLength() + 1,
					$targetType->range()->maxLength() === PlusInfinity::value ?
						PlusInfinity::value : $targetType->range()->maxLength() + 1
				);
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof DictValue) {
			if ($parameter instanceof DictValue) {
				$p = $parameter->values();
				$pKey = $p['key'] ?? null;
				$pValue = $p['value'] ?? null;
				if ($pValue && $pKey instanceof StringValue) {
					$values = $targetValue->values();
					$values[$pKey->literalValue()] = $pValue;
					return $this->context->valueRegistry->dict($values);
				}
			}
			// @codeCoverageIgnoreStart
			throw new ExecutionException("Invalid parameter value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}