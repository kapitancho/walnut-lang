<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownProperty;
use Walnut\Lang\Blueprint\Value\ListValue as ListValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class ListValue implements ListValueInterface {

	private TupleType $type;

	/**
	 * @param TypeRegistry $typeRegistry
	 * @param list<Value> $values
	 */
    public function __construct(
        private TypeRegistry $typeRegistry,
	    private array $values
    ) {}

    public function type(): TupleType {
        return $this->type ??= $this->typeRegistry->tuple(
			array_map(
				static fn(Value $value): Type =>
					$value->type(), $this->values
			),
	        $this->typeRegistry->nothing()
        );
    }

	/** @return list<Value> */
	public function values(): array {
		return $this->values;
	}

	/** @throws UnknownProperty */
	public function valueOf(int $index): Value {
		return $this->values[$index] ??
			throw new UnknownProperty(new PropertyNameIdentifier($index), (string)$this);
	}

	public function equals(Value $other): bool {
		if ($other instanceof ListValueInterface) {
			$thisValues = $this->values;
			$otherValues = $other->values();
			if (count($thisValues) === count($otherValues)) {
				foreach($thisValues as $index => $value) {
					if (!$value->equals($otherValues[$index])) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

	public function __toString(): string {
		return sprintf(
			"[%s]",
			implode(', ', array_map(
				static fn(Value $value): string => (string)$value, $this->values
			))
		);
	}
}