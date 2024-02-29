<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Range\IntegerRange;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Type\IntegerType as IntegerTypeInterface;
use Walnut\Lang\Blueprint\Type\RealType as RealTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;

final readonly class IntegerType implements IntegerTypeInterface {

    public function __construct(
		private TypeRegistry $typeRegistry,
		private IntegerRange $range
    ) {}

    public function range(): IntegerRange {
        return $this->range;
    }

    public function isSubtypeOf(Type $ofType): bool {
        return match(true) {
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
}