<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\IntersectionType as IntersectionTypeInterface;

final readonly class IntersectionType implements IntersectionTypeInterface, SupertypeChecker {
	/** @var non-empty-list<Type> $types */
	private array $types;

	public function __construct(
        Type ... $types
	) {
		$this->types = $types;
	}

    /**
     * @return list<Type>
     */
    public function types(): array {
        return $this->types;
    }

    public function isSubtypeOf(Type $ofType): bool {
        foreach($this->types as $type) {
            if ($type->isSubtypeOf($ofType)) {
                return true;
            }
        }
        return $ofType instanceof SupertypeChecker && $ofType->isSupertypeOf($this);
    }

    public function isSupertypeOf(Type $ofType): bool {
        foreach($this->types as $type) {
            if (!$ofType->isSubtypeOf($type)) {
                return false;
            }
        }
        return true;
    }

	public function __toString(): string {
		return sprintf("(%s)", implode('&', $this->types));
	}
}