<?php

namespace Walnut\Lang\Blueprint\Registry;

use Walnut\Lang\Blueprint\Execution\Program;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope;
use Walnut\Lang\Blueprint\Expression\ConstantExpression;
use Walnut\Lang\Blueprint\Expression\ConstructorCallExpression;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\FunctionCallExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression;
use Walnut\Lang\Blueprint\Expression\PropertyAccessExpression;
use Walnut\Lang\Blueprint\Expression\RecordExpression;
use Walnut\Lang\Blueprint\Expression\ReturnExpression;
use Walnut\Lang\Blueprint\Expression\SequenceExpression;
use Walnut\Lang\Blueprint\Expression\TupleExpression;
use Walnut\Lang\Blueprint\Expression\VariableAssignmentExpression;
use Walnut\Lang\Blueprint\Expression\VariableNameExpression;
use Walnut\Lang\Blueprint\Function\CustomMethod;
use Walnut\Lang\Blueprint\Function\FunctionBody;
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
use Walnut\Lang\Blueprint\Type\MetaType;
use Walnut\Lang\Blueprint\Type\MutableType;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\NullType;
use Walnut\Lang\Blueprint\Type\RealSubsetType;
use Walnut\Lang\Blueprint\Type\RealType;
use Walnut\Lang\Blueprint\Type\RecordType;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TrueType;
use Walnut\Lang\Blueprint\Type\TupleType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\TypeType;
use Walnut\Lang\Blueprint\Type\UnionType;
use Walnut\Lang\Blueprint\Value\AtomValue;
use Walnut\Lang\Blueprint\Value\BooleanValue;
use Walnut\Lang\Blueprint\Value\DictValue;
use Walnut\Lang\Blueprint\Value\EnumerationValue;
use Walnut\Lang\Blueprint\Value\FunctionValue;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\ListValue;
use Walnut\Lang\Blueprint\Value\MutableValue;
use Walnut\Lang\Blueprint\Value\NullValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

interface ProgramBuilder {
	/**
	 * @return array{
	 *     constant: callable(Value): ConstantExpression,
	 *     tuple: callable(list<Expression>): TupleExpression,
	 *     record: callable(array<string, Expression>): RecordExpression,
	 *     sequence: callable(list<Expression>): SequenceExpression,
	 *     return: callable(Expression): ReturnExpression,
	 *     var: callable(string): VariableNameExpression,
	 *     assign: callable(string, Expression): VariableAssignmentExpression,
	 *     matchValue: callable(Expression, list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression,
	 *     matchType: callable(Expression, list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression,
	 *     matchTrue: callable(list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression,
	 *     matchIf: callable(Expression, Expression, Expression): MatchExpression,
	 *     matchPair: callable(Expression, Expression): MatchExpressionPair,
	 *     matchDefault: callable(Expression): MatchExpressionDefault,
	 *     call: callable(Expression, Expression): FunctionCallExpression,
	 *     method: callable(Expression, string, Expression): MethodCallExpression,
	 *     constructor: callable(string, Expression): ConstructorCallExpression,
	 *     property: callable(Expression, string): PropertyAccessExpression,
	 *     functionBody: callable(Expression): FunctionBody,
	 * }
	 */
	public function expressionRegistry(): array;

	/**
	 * @return array{
	 *     addVariable: callable(string, Value): VariableValuePair,
	 * }
	 */
	public function valueBuilder(): array;

	/**
	 * @return array{
	 *     addAtom: callable(string): AtomType,
	 *     addEnumeration: callable(string, list<string>): EnumerationType,
	 *     addAlias: callable(string, Type): AliasType,
	 *     addSubtype: callable(string, Type, FunctionBody): SubtypeType,
	 * }
	 */
	public function typeBuilder(): array;

	/**
	 * @return array{
	 *     Atom: callable(string): AtomType,
	 *     Enumeration: callable(string): EnumerationType,
	 *     Alias: callable(string): AliasType,
	 *     Subtype: callable(string): SubtypeType,
	 *     NamedType: callable(string): Type,
	 *     Any: callable(): AnyType,
	 *     Nothing: callable(): NothingType,
	 *     Boolean: callable(): BooleanType,
	 *     True: callable(): TrueType,
	 *     False: callable(): FalseType,
	 *     Null: callable(): NullType,
	 *     Mutable: callable(Type): MutableType,
	 *     Function: callable(Type, Type): FunctionType,
	 *     Union: callable(non-empty-list<Type>): UnionType,
	 *     Intersection: callable(non-empty-list<Type>): IntersectionType,
	 *     MetaType: callable(string): MetaType,
	 *     Type: callable(Type): TypeType,
	 *     String: callable(int, int): StringType,
	 *     Integer: callable(int, int): IntegerType,
	 *     Real: callable(float, float): RealType,
	 *     Array: callable(Type, int, int): ArrayType,
	 *     Map: callable(Type, int, int): MapType,
	 *     Tuple: callable(list<Type>): TupleType,
	 *     Record: callable(array<string, Type>): RecordType,
	 *     IntegerSubset: callable(list<int>): IntegerSubsetType,
	 *     RealSubset: callable(list<float>): RealSubsetType,
	 *     StringSubset: callable(list<string>): StringSubsetType,
	 *     EnumerationSubset: callable(string, list<string>): EnumerationSubsetType,
	 * }
	 */
	public function typeRegistry(): array;

	/**
	 * @return array{
	 *     boolean: callable(bool): BooleanValue,
	 *     true: callable(): BooleanValue,
	 *     false: callable(): BooleanValue,
	 *     null: callable(): NullValue,
	 *     integer: callable(int): IntegerValue,
	 *     real: callable(float): RealValue,
	 *     string: callable(string): StringValue,
	 *     list: callable(list<Value>): ListValue,
	 *     dict: callable(array<string, Value>): DictValue,
	 *     function: callable(string, list<string>, FunctionBody): FunctionValue,
	 *     type: callable(Type): TypeValue,
	 *     mutable: callable(Value): MutableValue,
	 *     atom: callable(string): AtomValue,
	 *     enumeration: callable(string, string): EnumerationValue,
	 *     subtype: callable(string, Value): Value,
	 *     vars: callable(): VariableValueScope,
	 * }
	 */
	public function valueRegistry(): array;

	/**
	 * @return array{
	 *     addMethod: callable(Type, string, Type, Type|null, Type, FunctionBody): CustomMethod,
	 * }
	 */
	public function methodBuilder(): array;

	public function build(): Program;
}