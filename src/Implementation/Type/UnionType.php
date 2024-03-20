<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MetaTypeValue;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnionType as UnionTypeInterface;

final readonly class UnionType implements UnionTypeInterface, SupertypeChecker, JsonSerializable {
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
            if (!$type->isSubtypeOf($ofType)) {
                return $ofType instanceof SupertypeChecker && $ofType->isSupertypeOf($this);
            }
        }
        return true;
    }

    public function isSupertypeOf(Type $ofType): bool {
        foreach($this->types as $type) {
            if ($ofType->isSubtypeOf($type)) {
                return true;
            }
        }
        return false;
    }

	public function __toString(): string {
		return sprintf("(%s)", implode('|', $this->types));
	}

	public function jsonSerialize(): array {
		return ['type' => 'Union', 'types' => $this->types];
	}
}