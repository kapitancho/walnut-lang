<?php

namespace Walnut\Lang\Implementation\NativeCode;

use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\AnyType;
use Walnut\Lang\Blueprint\Type\ArrayType;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\BooleanType;
use Walnut\Lang\Blueprint\Type\EnumerationSubsetType;
use Walnut\Lang\Blueprint\Type\EnumerationType;
use Walnut\Lang\Blueprint\Type\FalseType;
use Walnut\Lang\Blueprint\Type\FunctionType;
use Walnut\Lang\Blueprint\Type\IntegerSubsetType;
use Walnut\Lang\Blueprint\Type\IntegerType;
use Walnut\Lang\Blueprint\Type\IntersectionType;
use Walnut\Lang\Blueprint\Type\MapType;
use Walnut\Lang\Blueprint\Type\MutableType;
use Walnut\Lang\Blueprint\Type\NamedType;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\NullType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\ResultType;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TrueType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Type\UnionType;

final readonly class NativeCodeTypeMapper {

	private function getTypeMapping(): array {
		return [
			ArrayType::class => ['Array'],
			MapType::class => ['Map'],
			TupleType::class => ['Tuple', 'Array'],
			RecordType::class => ['Record', 'Map'],
			IntegerType::class => ['Integer', 'Real'],
			IntegerSubsetType::class => ['Integer', 'Real'],
			RealType::class => ['Real'],
			RealSubsetType::class => ['Real'],
			StringType::class => ['String'],
			StringSubsetType::class => ['String'],
			BooleanType::class => ['Boolean', 'Enumeration'],
			TrueType::class => ['Boolean', 'Enumeration'],
			FalseType::class => ['Boolean', 'Enumeration'],
			NullType::class => ['Null', 'Atom'],
			EnumerationType::class => ['Enumeration'],
			EnumerationSubsetType::class => ['Enumeration'],
			AtomType::class => ['Atom'],
			SubtypeType::class => ['Subtype'],
			StateType::class => ['State'],
			AliasType::class => ['Alias'],
			FunctionType::class => ['Function'],
			ResultType::class => ['Result'],
			MutableType::class => ['Mutable'],
			TypeType::class => ['Type'],
			UnionType::class => ['Union'],
			IntersectionType::class => ['Intersection'],
			NothingType::class => ['Nothing'],
			AnyType::class => [],
		];
	}

	/**
	 * @param Type $type
	 * @return array<string>
	 */
	private function findTypesFor(Type $type): array {
		$baseIds = [];
		$k = 0;
		$alias = null;
		$subtype = null;
		while ($type instanceof AliasType || $type instanceof SubtypeType) {
			$k++;
			$baseIds[] = $type->name()->identifier;
			if ($type instanceof AliasType) {
				$alias ??= $k;
				$type = $type->aliasedType();
				continue;
			}
            $subtype ??= $k;
            $type = $type->baseType();
		}
		if ($alias !== null) {
			if ($subtype !== null && $subtype < $alias) {
				$baseIds[] = 'Subtype';
			}
			$baseIds[] = 'Alias';
			if ($subtype !== null && $subtype > $alias) {
				$baseIds[] = 'Subtype';
			}
		} elseif ($subtype !== null) {
			$baseIds[] = 'Subtype';
		}

		foreach ($this->getTypeMapping() as $typeClass => $ids) {
			if ($type instanceof $typeClass) {
				return array_merge($baseIds, $ids);
			}
		}
		// @codeCoverageIgnoreStart
		return []; //should never reach this point
		// @codeCoverageIgnoreEnd
	}

	/**
	 * @param Type $type
	 * @return array<string>
	 */
	public function getTypesFor(Type $type): array {
		$result = $this->findTypesFor($type);
		$result[] = 'Any';
		if ($type instanceof NamedType) {
			array_unshift($result, $type->name()->identifier);
		}
		return $result;
	}
}