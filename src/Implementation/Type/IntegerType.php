<?php

namespace Walnut\Lang\Implementation\Type;

use JsonSerializable;
use Walnut\Lang\Blueprint\Range\IntegerRange;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType as IntegerTypeInterface;
use Walnut\Lang\Blueprint\Type\RealType as RealTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\IntegerValue;

final readonly class IntegerType implements IntegerTypeInterface, JsonSerializable {

    public function __construct(
		private TypeRegistry $typeRegistry,
		private IntegerRange $range
    ) {}

    public function range(): IntegerRange {
        return $this->range;
    }

    public function isSubtypeOf(Type $ofType): bool {
        return match(true) {
            $ofType instanceof IntegerSubsetType => $this->isSubtypeOfSubset($ofType),
            $ofType instanceof IntegerTypeInterface =>
                $this->range->isSubRangeOf($ofType->range()),
            $ofType instanceof RealTypeInterface =>
                $this->asRealType()->isSubTypeOf($ofType),
            $ofType instanceof SupertypeChecker =>
                $ofType->isSupertypeOf($this),
            default => false
        };
    }

    private function asRealType(): RealType {
	    $range = $this->range->asRealRange();
        return $this->typeRegistry->real($range->minValue(), $range->maxValue());
    }

	public function __toString(): string {
		$range = (string)$this->range;
		return sprintf("Integer%s", $range === '..' ? '' : "<$range>");
	}

	private function isSubtypeOfSubset(IntegerSubsetType $ofType): bool {
		$min = $this->range->minValue();
		$max = $this->range->maxValue();
		return $min !== MinusInfinity::value && $max !== PlusInfinity::value &&
			$ofType->range()->minValue() <= $min && $ofType->range()->maxValue() >= $max &&
			1 + $this->range->maxValue() - $this->range->minValue() === count(
				array_filter($ofType->subsetValues(), fn(IntegerValue $value): bool =>
					$value->literalValue() >= $min && $value->literalValue() <= $max
			));
	}

	public function jsonSerialize(): array {
		return [
			'type' => 'Integer',
			'range' => $this->range
		];
	}
}