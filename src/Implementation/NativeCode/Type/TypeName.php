<?php

namespace Walnut\Lang\Implementation\NativeCode\Type;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Helper\BaseTypeHelper;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\Type as TypeInterface;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class TypeName implements Method {

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
			if ($refType instanceof MetaType) {
				if (in_array($refType->value(), [
					MetaTypeValue::Named,
					MetaTypeValue::Atom,
					MetaTypeValue::Enumeration,
					MetaTypeValue::Alias,
					MetaTypeValue::Subtype,
					MetaTypeValue::State,
				], true)) {
					return $this->context->typeRegistry->string(1);
				}
			}
			if ($refType instanceof NamedType) {
				return $this->context->typeRegistry->stringSubset([$refType->name()->identifier]);
			}
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
		if ($targetValue instanceof TypeValue) {
			$typeValue = $targetValue->typeValue();
			if ($typeValue instanceof NamedType) {
				return $this->context->valueRegistry->string($typeValue->name()->identifier);
			}
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid parameter value");
		// @codeCoverageIgnoreEnd
	}

}