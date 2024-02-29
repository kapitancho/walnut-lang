<?php

namespace Walnut\Lang\Implementation\NativeCode;

use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class UnionMethodCall implements Method {

	public function __construct(
		private NativeCodeContext $context,
		private array $methods
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		return $this->context->typeRegistry->union(
			array_map(
				static fn(array $method): Type => $method[1]->analyse(
					$method[0], $parameterType, $dependencyType
				),
				$this->methods
			)
		);
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		foreach($this->methods as [$methodType, $method]) {
			 if ($targetValue->type()->isSubtypeOf($methodType)) {
				 return $method->execute($targetValue, $parameter, $dependencyValue);
			 }
		}
		throw new ExecutionException("Union method call is not executable");
	}

}