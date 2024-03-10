<?php

use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Expression\ConstantExpression;
use Walnut\Lang\Blueprint\Expression\ConstructorCallExpression;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\FunctionCallExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpression;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Expression\MethodCallExpression;
use Walnut\Lang\Blueprint\Expression\NoErrorExpression;
use Walnut\Lang\Blueprint\Expression\PropertyAccessExpression;
use Walnut\Lang\Blueprint\Expression\RecordExpression;
use Walnut\Lang\Blueprint\Expression\ReturnExpression;
use Walnut\Lang\Blueprint\Expression\SequenceExpression;
use Walnut\Lang\Blueprint\Expression\TupleExpression;
use Walnut\Lang\Blueprint\Expression\VariableAssignmentExpression;
use Walnut\Lang\Blueprint\Expression\VariableNameExpression;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
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
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;

/* @var callable(string, Value): VariableValuePair $addVariable */

/* @var callable(string): AtomType $addAtom */
/* @var callable(string, list<string>): EnumerationType $addEnumeration */
/* @var callable(string, Type): AliasType $addAlias */
/* @var callable(string, Type, FunctionBody, Type|null): SubtypeType $addSubtype */
/* @var callable(string, Type): StateType $addState */

/* @var callable(string): AtomType $Atom */
/* @var callable(string): EnumerationType $Enumeration */
/* @var callable(string): AliasType $Alias */
/* @var callable(string): SubtypeType $Subtype */
/* @var callable(string): StateType $State */
/* @var callable(string): Type $NamedType */
/* @var callable(): AnyType $Any */
/* @var callable(): NothingType $Nothing */
/* @var callable(): BooleanType $Boolean */
/* @var callable(): TrueType $True */
/* @var callable(): FalseType $False */
/* @var callable(): NullType $Null */
/* @var callable(Type): MutableType $Mutable */
/* @var callable(Type, Type): FunctionType $Function */
/* @var callable(list<Type>): UnionType $Union */
/* @var callable(list<Type>): IntersectionType $Intersection */
/* @var callable(Type, Type): ResultType $Result */
/* @var callable(Type): TypeType $Type */
/* @var callable(int, int|PlusInfinity): StringType $String */
/* @var callable(int|MinusInfinity, int|PlusInfinity): IntegerType $Integer */
/* @var callable(float|MinusInfinity, float|PlusInfinity): RealType $Real */
/* @var callable(Type, int, int|PlusInfinity): ArrayType $Array */
/* @var callable(Type, int, int|PlusInfinity): MapType $Map */
/* @var callable(list<Type>): TupleType $Tuple */
/* @var callable(array<string, Type>): RecordType $Record */
/* @var callable(list<int>): IntegerSubsetType $IntegerSubset */
/* @var callable(list<float>): RealSubsetType $RealSubset */
/* @var callable(list<string>): StringSubsetType $StringSubset */
/* @var callable(string, list<string>): EnumerationSubsetType $EnumerationSubset */

/* @var callable(bool): BooleanValue $boolean */
/* @var callable(): BooleanValue $true */
/* @var callable(): BooleanValue $false */
/* @var callable(): NullValue $null */
/* @var callable(int): IntegerValue $integer */
/* @var callable(float): RealValue $real */
/* @var callable(string): StringValue $string */
/* @var callable(list<Value>): ListValue $list */
/* @var callable(array<string, Value>): DictValue $dict */
/* @var callable(string, list<string>, FunctionBody): FunctionValue $function */
/* @var callable(Value): ErrorValue $error */
/* @var callable(Type): TypeValue $type */
/* @var callable(Value): MutableValue $mutable */
/* @var callable(string): AtomValue $atom */
/* @var callable(string, string): EnumerationValue $enumeration */
/* @var callable(string, Value): Value $subtype */
/* @var callable(string, Value): Value $state */
/* @var callable(): VariableValuePair $variables */

/* @var callable(Value): ConstantExpression $constant */
/* @var callable(list<Expression>): TupleExpression $tuple */
/* @var callable(array<string, Expression>): RecordExpression $record */
/* @var callable(list<Expression>): SequenceExpression $sequence */
/* @var callable(Expression): ReturnExpression $return */
/* @var callable(Expression): NoErrorExpression $noError */
/* @var callable(string): VariableNameExpression $var */
/* @var callable(string, Expression): VariableAssignmentExpression $assign */
/* @var callable(Expression, list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression $matchValue */
/* @var callable(list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression $matchTrue */
/* @var callable(Expression, list<MatchExpressionPair|MatchExpressionDefault>): MatchExpression $matchType */
/* @var callable(Expression, Expression, Expression): MatchExpression $matchIf */
/* @var callable(Value, Expression): MatchExpressionPair $matchPair */
/* @var callable(Expression): MatchExpressionDefault $matchDefault */
/* @var callable(Expression, Expression): FunctionCallExpression $call */
/* @var callable(Expression, string, Expression): MethodCallExpression $method */
/* @var callable(string, Expression): ConstructorCallExpression $constructor */
/* @var callable(Expression, string): PropertyAccessExpression $property */
/* @var callable(Expression): FunctionBody $functionBody */

/* @var callable(Type, string, Type, Type|null, Type, FunctionBody): void $addMethod */

$addAtom("Success");
$addEnumeration("Suit", ["Hearts", "Diamonds", "Clubs", "Spades"]);
$addSubtype("PositiveInteger", $Integer(), $functionBody(
	$constant($null())
), null);

$myVar = $assign("myVar", $record([
	'null' => $constant($null()),
	'boolean' => $constant($true()),
	'real' => $constant($real(3.14)),
	'integer' => $constant($integer(42)),
	'string' => $constant($string("Hello")),
	'type' => $constant($type($Any())),
	'others' => $tuple([
		$constant($enumeration("Suit", "Spades")),
		$constant($atom("Success")),
		$constant($function($Any(), $String(), $functionBody(
			$constant($string("Test"))
		))),
		$constructor("PositiveInteger", $constant($integer(333))),
	]),
	'input' => $var('#')
]));

$addMethod(
	$Array($Any(), 0, PlusInfinity::value),
	new MethodNameIdentifier("customMethod"),
	$String(),
	null,
	$String(),
	$functionBody(
		$var('#')
	)
);

$mainFn = $function($Any(), $String(), $functionBody(
	$sequence([
		$myVar,
		$method(
			$property($myVar, "others"),
			"customMethod",
			$constant($string("Hello"))
		),
	])
));
$mainFn->analyse();
$addVariable('main', $mainFn);
