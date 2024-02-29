<?php

namespace Walnut\Lang\Implementation\NativeCode\Array;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class WithoutLast implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof ArrayType) {
			$returnType = $this->context->typeRegistry->record([
				'element' => $targetType->itemType(),
				'array' => $this->context->typeRegistry->array(
					$targetType->itemType(),
					max(0, $targetType->range()->minLength() - 1),
					$targetType->range()->maxLength() === PlusInfinity::value ?
						PlusInfinity::value : $targetType->range()->maxLength() - 1
				)
			]);
			return $targetType->range()->minLength() > 0 ? $returnType :
				$this->context->typeRegistry->result($returnType,
					$this->context->typeRegistry->atom(
						new TypeNameIdentifier("ItemNotFound")
					)
				);
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
		if ($targetValue instanceof ListValue) {
			$values = $targetValue->values();
			if (count($values) === 0) {
				return $this->context->valueRegistry->atom(
					new TypeNameIdentifier("ItemNotFound")
				);
			}
			$element = array_pop($values);
			return $this->context->valueRegistry->dict([
				'element' => $element,
				'array' => $this->context->valueRegistry->list($values)
			]);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}