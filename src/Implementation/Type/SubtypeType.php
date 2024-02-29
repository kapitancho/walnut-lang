<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\SubtypeType as SubtypeTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class SubtypeType implements SubtypeTypeInterface {

    public function __construct(
	    private TypeNameIdentifier $typeName,
        private Type $baseType,
        private FunctionBody $constructorBody,
        private Type|null $errorType,
    ) {}

	public function name(): TypeNameIdentifier {
		return $this->typeName;
    }

	public function baseType(): Type {
        return $this->baseType;
    }

	public function constructorBody(): FunctionBody {
		return $this->constructorBody;
	}

	public function errorType(): Type|null {
        return $this->errorType;
    }

	public function isSubtypeOf(Type $ofType): bool {
        return (
		    $ofType instanceof self &&
		    $this->typeName->equals($ofType->typeName)
        ) || (
            $this->baseType->isSubtypeOf($ofType)
        ) || (
            $ofType instanceof SupertypeChecker &&
            $ofType->isSupertypeOf($this)
        );
    }

	public function __toString(): string {
		return (string)$this->typeName;
	}
}