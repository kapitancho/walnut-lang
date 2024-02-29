<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Blueprint\Value\AtomValue;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\EnumerationValue;
use Walnut\Lang\Blueprint\Value\ErrorValue;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StateValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\UnknownEnumerationValue;
use Walnut\Lang\Blueprint\Value\Value;

interface ValueRegistry {
    public function null(): NullValue;
    public function boolean(bool $value): BooleanValue;
    public function true(): BooleanValue;
    public function false(): BooleanValue;
    public function integer(int $value): IntegerValue;
    public function real(float $value): RealValue;
    public function string(string $value): StringValue;

	/** @param list<Value> $values */
	public function list(array $values): ListValue;
	/** @param array<string, Value> $values */
	public function dict(array $values): DictValue;

    public function function(
		Type $parameterType, Type $returnType, FunctionBody $body
    ): FunctionValue;

	public function mutable(Type $type, Value $value): MutableValue;
    public function type(Type $type): TypeValue;
    public function error(Value $value): ErrorValue;

    /** @throws UnknownType */
    public function atom(TypeNameIdentifier $typeName): AtomValue;

    /** @throws UnknownType */
    /** @throws UnknownEnumerationValue */
    public function enumerationValue(
        TypeNameIdentifier $typeName,
        EnumValueIdentifier $valueIdentifier
    ): EnumerationValue;

    /** @throws UnknownType */
    public function subtypeValue(
        TypeNameIdentifier $typeName,
        Value $baseValue
    ): SubtypeValue;

	/** @throws UnknownType */
    public function stateValue(
        TypeNameIdentifier $typeName,
        Value $stateValue
    ): StateValue;

	public function variables(): VariableValueScope;
}