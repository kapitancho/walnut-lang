<?php

namespace Walnut\Lang\Implementation\NativeCode\Clock;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\AtomValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class Now implements Method {

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof AtomType && $targetType->name()->equals(
			new TypeNameIdentifier('Clock')
		)) {
			return $this->context->typeRegistry->withName(new TypeNameIdentifier('DateAndTime'));
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(Value $targetValue, Value $parameter, ?Value $dependencyValue,): Value|TypedValue {
		$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof AtomValue && $targetValue->type()->name()->equals(
			new TypeNameIdentifier('Clock')
		)) {
			$now = new \DateTimeImmutable;
			return $this->context->valueRegistry->subtypeValue(
				new TypeNameIdentifier('DateAndTime'),
				$this->context->valueRegistry->dict([
					'date' => $this->context->valueRegistry->dict([
						'year' => $this->context->valueRegistry->integer((int)$now->format('Y')),
						'month' => $this->context->valueRegistry->integer((int)$now->format('m')),
						'day' => $this->context->valueRegistry->integer((int)$now->format('d')),
					]),
					'time' => $this->context->valueRegistry->dict([
						'hour' => $this->context->valueRegistry->integer((int)$now->format('H')),
						'minute' => $this->context->valueRegistry->integer((int)$now->format('i')),
						'second' => $this->context->valueRegistry->integer((int)$now->format('s')),
					]),
				])
			);
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}
}