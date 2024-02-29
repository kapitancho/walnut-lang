<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownProperty;
use Walnut\Lang\Blueprint\Value\DictValue as DictValueInterface;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class DictValue implements DictValueInterface {

	private RecordType $type;

	/**
	 * @param TypeRegistry $typeRegistry
	 * @param array<string, Value> $values
	 */
    public function __construct(
        private TypeRegistry $typeRegistry,
	    private array $values
    ) {}

    public function type(): RecordType {
        return $this->type ??= $this->typeRegistry->record(
			array_map(
				static fn(Value $value): Type =>
					$value->type(), $this->values
			),
	        $this->typeRegistry->nothing()
        );
    }

	/** @return array<string, Value> */
	public function values(): array {
		return $this->values;
	}

	/** @throws UnknownProperty */
	public function valueOf(PropertyNameIdentifier $propertyName): Value {
		return $this->values[$propertyName->identifier] ??
			throw new UnknownProperty($propertyName, (string)$this);
	}

	public function equals(Value $other): bool {
		if ($other instanceof DictValueInterface) {
			$thisValues = $this->values;
			$otherValues = $other->values();
			if (count($thisValues) === count($otherValues)) {
				foreach($thisValues as $key => $value) {
					if (
						!array_key_exists($key, $otherValues) ||
						!$value->equals($otherValues[$key])
					) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

	public function __toString(): string {
		return count($this->values) ? sprintf(
			"[%s]",
			implode(', ', array_map(
				static fn(string $key, Value $value): string =>
					sprintf("%s: %s", $key, $value),
				array_keys($this->values), $this->values
			))
		) : '[:]';
	}
}