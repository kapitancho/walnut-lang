<?php

namespace Walnut\Lang\Implementation\NativeCode\Map;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Item implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		$type = $targetType instanceof RecordType ? $targetType->asMapType() : $targetType;
		if ($targetType instanceof MetaType && $targetType->value() === MetaTypeValue::Record) {
			$type = $this->context->typeRegistry->map(
				$this->context->typeRegistry->any()
			);
		}
		if ($type instanceof MapType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof StringType || $parameterType instanceof StringSubsetType) {
				$returnType = $type->itemType();
				if ($targetType instanceof RecordType && $parameterType instanceof StringSubsetType) {
					$returnType = $this->context->typeRegistry->union(
						array_map(
							fn(StringValue $value) =>
								$targetType->types()[$value->literalValue()] ??
								$targetType->restType(),
							$parameterType->subsetValues()
						)
					);
					$allKeys = array_filter($parameterType->subsetValues(),
						fn(StringValue $value) => array_key_exists($value->literalValue(), $targetType->types())
					);
					if (count($allKeys) === count($parameterType->subsetValues())) {
						return $returnType;
					}
				}
				return $this->context->typeRegistry->result(
					$returnType,
					$this->context->typeRegistry->state(
						new TypeNameIdentifier("MapItemNotFound")
					)
				);
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType    ));
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
		if ($targetValue instanceof DictValue && $parameter instanceof StringValue) {
			$values = $targetValue->values();
			$result = $values[$parameter->literalValue()] ?? null;
			return $result ?? $this->context->valueRegistry->error(
				$this->context->valueRegistry->stateValue(
					new TypeNameIdentifier('MapItemNotFound'),
					$this->context->valueRegistry->dict(['key' => $parameter])
				)
			);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}