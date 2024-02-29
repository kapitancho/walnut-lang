<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Type\FunctionType as FunctionTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class FunctionType implements FunctionTypeInterface {

	private Type $realParameterType;
	private Type $realReturnType;

    public function __construct(
        private Type $parameterType,
        private Type $returnType
    ) {}

    public function parameterType(): Type {
		return $this->realParameterType ??= $this->parameterType instanceof ProxyNamedType ?
			$this->parameterType->getActualType() : $this->parameterType;
	}

	public function returnType(): Type {
		return $this->realReturnType ??= $this->returnType instanceof ProxyNamedType ?
			$this->returnType->getActualType() : $this->returnType;
	}

    public function isSubtypeOf(Type $ofType): bool {
        return (
            $ofType instanceof FunctionTypeInterface &&
            $ofType->parameterType()->isSubtypeOf($this->parameterType()) &&
            $this->returnType()->isSubtypeOf($ofType->returnType())
        ) || (
            $ofType instanceof SupertypeChecker &&
            $ofType->isSupertypeOf($this)
        );
    }

	public function __toString(): string {
		return sprintf(
			'^%s => %s',
			$this->parameterType(),
			$this->returnType(),
		);
	}
}