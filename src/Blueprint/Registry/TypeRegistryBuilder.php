<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\StateType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\EnumerationType;

interface TypeRegistryBuilder {
    public function addAtom(TypeNameIdentifier $name): AtomType;

    /** @param list<EnumValueIdentifier> $values */
    public function addEnumeration(TypeNameIdentifier $name, array $values): EnumerationType;

	public function addAlias(TypeNameIdentifier $name, Type $aliasedType): AliasType;

	public function addSubtype(
		TypeNameIdentifier $name,
		Type $baseType,
		FunctionBody $constructorBody,
		Type|null $errorType
	): SubtypeType;

	public function addState(
		TypeNameIdentifier $name,
		Type $stateType
	): StateType;

	public function build(): TypeRegistry;

	public function analyse(): void;
}