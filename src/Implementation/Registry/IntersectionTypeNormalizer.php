<?php

namespace Walnut\Lang\Implementation\Registry;

use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Implementation\Type\IntersectionType;
use Walnut\Lang\Implementation\Type\ResultType;

final readonly class IntersectionTypeNormalizer {
    public function __construct(private TypeRegistry $typeRegistry) {}

    public function normalize(Type ... $types): Type {
        $parsedTypes = $this->parseTypes($types);
        if (count($parsedTypes) === 0) {
            return $this->typeRegistry->any();
        }
        if (count($parsedTypes) === 1) {
            return $parsedTypes[0];
        }
        return new IntersectionType($this, ...$parsedTypes);
    }

    private function parseTypes(array $types): array {
        $queue = [];
        foreach ($types as $type) {
            $xType = $type;
            while ($xType instanceof AliasType) {
                $xType = $xType->aliasedType();
            }
            $pTypes = $xType instanceof IntersectionType ?
                $this->parseTypes($xType->types()) : [$xType];
            foreach ($pTypes as $tx) {
                foreach ($queue as $qt) {
                    if ($qt->isSubtypeOf($tx)) {
                        continue 2;
                    }
                }
                for($ql = count($queue) - 1; $ql >= 0; $ql--) {
                    $q = $queue[$ql];
                    if ($tx->isSubtypeOf($q)) {
                        array_splice($queue, $ql, 1);
                    /*} else if ($q instanceof RecordType && $tx instanceof RecordType) {
						echo 'URA';die;*/
                    } else if ($q instanceof ResultType || $tx instanceof ResultType) {
	                    array_splice($queue, $ql, 1);

						$returnTypes = [
							$q instanceof ResultType ? $q->returnType() : $q,
							$tx instanceof ResultType ? $tx->returnType() : $tx,
						];
						$errorTypes = [
							$q instanceof ResultType ? $q->errorType() : $this->typeRegistry->nothing(),
							$tx instanceof ResultType ? $tx->errorType() : $this->typeRegistry->nothing(),
						];
						$returnType = $this->normalize(... $returnTypes);
						$errorType = $this->normalize(... $errorTypes);
						$tx = $errorType instanceof NothingType ? $returnType :
							$this->typeRegistry->result($returnType, $errorType);
                    } else if ($q instanceof IntegerType && $tx instanceof IntegerType) {
                        $newRange = $q->range()->tryRangeIntersectionWith($tx->range());
                        if ($newRange) {
                            array_splice($queue, $ql, 1);
                            $tx = $this->typeRegistry->integer(
                                $newRange->minValue(), $newRange->maxValue()
                            );
                        }
                    } else if ($q instanceof IntegerSubsetType && $tx instanceof IntegerSubsetType) {
                        array_splice($queue, $ql, 1);
                        $intersectedValues = array_values(
                            array_intersect($q->subsetValues(), $tx->subsetValues())
                        );
                        $tx = $intersectedValues ? $this->typeRegistry->integerSubset(
                            $intersectedValues
                        ) : $this->typeRegistry->nothing();
                    }
                }
                $queue[] = $tx;
            }
        }
        return $queue;
    }
}