<?php

namespace Walnut\Lang\Blueprint\Helper;

use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\Value;

trait BaseTypeHelper {

	public function toBaseType(Type $targetType): Type {
		while ($targetType instanceof AliasType || $targetType instanceof SubtypeType) {
			if ($targetType instanceof AliasType) {
				$targetType = $targetType->aliasedType();
			}
			if ($targetType instanceof SubtypeType) {
				$targetType = $targetType->baseType();
			}
		}
		return $targetType;
	}

	public function toBaseValue(Value $targetValue): Value {
		while ($targetValue instanceof SubtypeValue) {
			$targetValue = $targetValue->baseValue();
		}
		return $targetValue;
	}

	public function isTupleCompatibleToRecord(TypeRegistry $typeRegistry, TupleType $tupleType, RecordType $recordType): bool {
		return $tupleType->isSubtypeOf($typeRegistry->tuple(array_values($recordType->types())));
	}

	public function getTupleAsRecord(ValueRegistry $valueRegistry, ListValue $listValue, RecordType $recordType): DictValue {
		$result = [];
		$index = 0;
		foreach($recordType->types() as $key => $value) {
			$result[$key] = $listValue->valueOf($index++);
		}
		return $valueRegistry->dict($result);
	}

}