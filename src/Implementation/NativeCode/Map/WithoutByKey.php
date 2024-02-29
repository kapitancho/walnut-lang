<?php

namespace Walnut\Lang\Implementation\NativeCode\Map;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class WithoutByKey implements Method {

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
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof StringType || $parameterType instanceof StringSubsetType) {
				$returnType = $this->context->typeRegistry->record([
					'element' => $targetType->itemType(),
					'map' => $this->context->typeRegistry->map(
						$targetType->itemType(),
						$targetType->range()->maxLength() === PlusInfinity::value ?
							$targetType->range()->minLength() : max(0,
							min(
								$targetType->range()->minLength() - 1,
								$targetType->range()->maxLength() - 1
							)),
						$targetType->range()->maxLength() === PlusInfinity::value ?
							PlusInfinity::value : $targetType->range()->maxLength() - 1
					)
				]);
				return $this->context->typeRegistry->result(
					$returnType,
					$this->context->typeRegistry->state(
						new TypeNameIdentifier("MapItemNotFound")
					)
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
			if ($parameter instanceof StringValue) {
				$values = $targetValue->values();
				if (!isset($values[$parameter->literalValue()])) {
					return $this->context->valueRegistry->error(
						$this->context->valueRegistry->stateValue(
							new TypeNameIdentifier('MapItemNotFound'),
							$this->context->valueRegistry->dict(['key' => $parameter])
						)
					);
				}
				$val = $values[$parameter->literalValue()];
				unset($values[$parameter->literalValue()]);
				return $this->context->valueRegistry->dict([
					'element' => $val,
					'map' => $this->context->valueRegistry->dict($values)
				]);
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