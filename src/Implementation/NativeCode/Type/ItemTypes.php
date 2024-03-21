<?php

namespace Walnut\Lang\Implementation\NativeCode\Type;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Type\UnionType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ItemTypes implements Method {

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
			$refType = $this->toBaseType($targetType->refType());
			if ($refType instanceof TupleType || $refType instanceof RecordType) {
				return $this->context->typeRegistry->type($refType->restType());
			}
			if ($refType instanceof MetaType) {
				if (in_array($refType->value(), [
					MetaTypeValue::Tuple, MetaTypeValue::Union, MetaTypeValue::Intersection
				], true)) {
					return $this->context->typeRegistry->array(
						$this->context->typeRegistry->type(
							$this->context->typeRegistry->any()
						)
					);
				}
				if ($refType->value() === MetaTypeValue::Record) {
					return $this->context->typeRegistry->map(
						$this->context->typeRegistry->type(
							$this->context->typeRegistry->any()
						)
					);
				}
			}
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
		if ($targetValue instanceof TypeValue) {
			$typeValue = $this->toBaseType($targetValue->typeValue());
			if ($typeValue instanceof TupleType || $typeValue instanceof UnionType || $typeValue instanceof IntersectionType) {
				return $this->context->valueRegistry->list(
					array_map(
						fn(TypeInterface $type) => $this->context->valueRegistry->type($type),
						$typeValue->types()
					)
				);
			}
			if ($typeValue instanceof RecordType) {
				return $this->context->valueRegistry->dict(
					array_map(
						fn(TypeInterface $type) => $this->context->valueRegistry->type($type),
						$typeValue->types()
					)
				);
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}