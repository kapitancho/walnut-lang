<?php

namespace Walnut\Lang\Implementation;

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
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class ToStringTest extends BaseProgramTestHelper {

    public function testToString(): void {
	    $functions = $this->builder->valueBuilder();
        extract($functions);
        /* @var callable(string, Value): VariableValuePair $addVariable */

        $functions = $this->builder->typeBuilder();
        extract($functions);
        /* @var callable(string): AtomType $addAtom */
        /* @var callable(string, list<string>): EnumerationType $addEnumeration */
        /* @var callable(string, Type): AliasType $addAlias */
        /* @var callable(string, Type, FunctionBody, Type|null): SubtypeType $addSubtype */
	    /* @var callable(string, Type): StateType $addState */

        $functions = $this->builder->typeRegistry();
        extract($functions);

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

        $functions = $this->builder->valueRegistry();
        extract($functions);

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

        $functions = $this->builder->expressionRegistry();
        extract($functions);

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
	    /* @var callable(Value, Expression): MatchExpressionPair $matchPair */
	    /* @var callable(Expression): MatchExpressionDefault $matchDefault */
        /* @var callable(Expression, Expression): FunctionCallExpression $call */
        /* @var callable(Expression, string, Expression): MethodCallExpression $method */
        /* @var callable(string, Expression): ConstructorCallExpression $constructor */
        /* @var callable(Expression, string): PropertyAccessExpression $property */
        /* @var callable(Expression): FunctionBody $functionBody */

        $functions = $this->builder->methodBuilder();
        extract($functions);
        /* @var callable(Type, string, Type, Type|null, Type, FunctionBody): void $addMethod */

	    $addAtom("Success");
        $addEnumeration("Suit", ["Hearts", "Diamonds", "Clubs", "Spades"]);
	    $addAlias("CardSuit", $Enumeration("Suit"));
        $addSubtype("PositiveInteger", $Integer(), $functionBody(
            $constant($null())
        ), null);
		$addState("InvalidInteger", $Integer());

		$this->assertEquals('Success', (string)$Atom('Success'));
		$this->assertEquals('Suit', (string)$Enumeration('Suit'));
		$this->assertEquals('CardSuit', (string)$Alias('CardSuit'));
		$this->assertEquals('PositiveInteger', (string)$Subtype('PositiveInteger'));
		$this->assertEquals('InvalidInteger', (string)$State('InvalidInteger'));
		$this->assertEquals('InvalidInteger', (string)$NamedType('InvalidInteger'));
		$this->assertEquals('Any', (string)$Any());
		$this->assertEquals('Nothing', (string)$Nothing());
		$this->assertEquals('Boolean', (string)$Boolean());
		$this->assertEquals('True', (string)$True());
		$this->assertEquals('False', (string)$False());
		$this->assertEquals('Null', (string)$Null());
		$this->assertEquals('Mutable<True>', (string)$Mutable($True()));
		$this->assertEquals('^True => String', (string)$Function($True(), $String()));
		$this->assertEquals('Boolean', (string)$Union([$True(), $False()]));
		$this->assertEquals('(True&False)', (string)$Intersection([$True(), $False()]));
		$this->assertEquals('Result<True, False>', (string)$Result($True(), $False()));
		$this->assertEquals('Type<True>', (string)$Type($True()));
		$this->assertEquals('String', (string)$String());
		$this->assertEquals('String<3..10>', (string)$String(3, 10));
		$this->assertEquals('String<3..>', (string)$String(3));
		$this->assertEquals('Integer', (string)$Integer());
		$this->assertEquals('Integer<..42>', (string)$Integer(max: 42));
		$this->assertEquals('Integer<-42..>', (string)$Integer(-42));
		$this->assertEquals('Integer<-42..42>', (string)$Integer(-42, 42));
		$this->assertEquals('Real', (string)$Real());
	    $this->assertEquals('Real<..42>', (string)$Real(max: 42));
        $this->assertEquals('Real<-42..>', (string)$Real(-42));
        $this->assertEquals('Real<-42..42>', (string)$Real(-42, 42));
		$this->assertEquals('Array', (string)$Array());
		$this->assertEquals('Array<True>', (string)$Array($True()));
		$this->assertEquals('Array<True, 3..10>', (string)$Array($True(), 3, 10));
		$this->assertEquals('Array<3..10>', (string)$Array($Any(), 3, 10));
	    $this->assertEquals('Map', (string)$Map());
        $this->assertEquals('Map<True>', (string)$Map($True()));
        $this->assertEquals('Map<True, 3..10>', (string)$Map($True(), 3, 10));
        $this->assertEquals('Map<3..10>', (string)$Map($Any(), 3, 10));
		$this->assertEquals('[]', (string)$Tuple([]));
		$this->assertEquals('[True, False]', (string)$Tuple([$True(), $False()]));
		$this->assertEquals('[:]', (string)$Record([]));
		$this->assertEquals('[~True, ~False]', (string)$Record(['true' => $True(), 'false' => $False()]));
		$this->assertEquals('Integer[-2, 1, 5]', (string)$IntegerSubset([-2, 1, 5]));
		$this->assertEquals('Real[2, 3.14]', (string)$RealSubset([2, 3.14]));
		$this->assertEquals("String['true', 'false']", (string)$StringSubset(['true', 'false']));
		$this->assertEquals('Suit[Spades, Clubs]', (string)$EnumerationSubset('Suit', ['Spades', 'Clubs']));


		$this->assertEquals('true', (string)$true());
		$this->assertEquals('false', (string)$false());
		$this->assertEquals('null', (string)$null());
		$this->assertEquals('1', (string)$integer(1));
		$this->assertEquals('3.14', (string)$real(3.14));
		$this->assertEquals("'Hello'", (string)$string('Hello'));
		$this->assertEquals('[]', (string)$list([]));
		$this->assertEquals('[1, 2, 3]', (string)$list([$integer(1), $integer(2), $integer(3)]));
		$this->assertEquals('[:]', (string)$dict([]));
		$this->assertEquals('[a: 1, b: 2]', (string)$dict(['a' => $integer(1), 'b' => $integer(2)]));
		$this->assertEquals('^Integer => Boolean :: true', (string)$function(
			$Integer(), $Boolean(), $functionBody($constant($true()))
		));
		$this->assertEquals('@InvalidInteger{1}', (string)$error($subtype('InvalidInteger', $integer(1))));
		$this->assertEquals('type{True}', (string)$type($True()));
	    $this->assertEquals('Mutable[Boolean, true]', (string)$mutable($Boolean(), $true()));
		$this->assertEquals('Success[]', (string)$atom('Success'));
		$this->assertEquals('Suit.Spades', (string)$enumeration('Suit', 'Spades'));
		$this->assertEquals('PositiveInteger{1}', (string)$subtype('PositiveInteger', $integer(1)));
		$this->assertEquals('InvalidInteger{1}', (string)$state('InvalidInteger', $integer(1)));

		$this->assertEquals('Success[]', (string)$constant($atom('Success')));
		$this->assertEquals('[x, y]', (string)$tuple([$var('x'), $var('y')]));
		$this->assertEquals('[a: x, b: y]', (string)$record(['a' => $var('x'), 'b' => $var('y')]));
		$this->assertEquals('{x; y}', (string)$sequence([$var('x'), $var('y')]));
		$this->assertEquals('=> x', (string)$return($var('x')));
		$this->assertEquals('?noError(x)', (string)$noError($var('x')));
		$this->assertEquals('x', (string)$var('x'));
		$this->assertEquals('x = y', (string)$assign('x', $var('y')));
		$this->assertEquals('?whenValueOf (x) == { true: y, ~: z }', (string)
			$matchValue($var('x'), [$matchPair($constant($true()), $var('y')), $matchDefault($var('z'))])
		);
		$this->assertEquals('?whenTypeOf (x) <: { t: y, ~: z }', (string)
			$matchType($var('x'), [$matchPair($var('t'), $var('y')), $matchDefault($var('z'))])
		);
	    $this->assertEquals('?whenIsTrue { x->asBoolean: y, ~: z }', (string)
            $matchTrue([$matchPair($var('x'), $var('y')), $matchDefault($var('z'))])
        );
	    /*$this->assertEquals('?ifTrue { x => y, ~ => z }', (string)
            $matchIf($var('x'), $var('y'), $var('z'))
        );*/
	    $this->assertEquals('x()', (string)$call($var('x')));
	    $this->assertEquals('x(y)', (string)$call($var('x'), $var('y')));
	    $this->assertEquals('x->y', (string)$method($var('x'), 'y'));
	    $this->assertEquals('x->y(z)', (string)$method($var('x'), 'y', $var('z')));
	    $this->assertEquals('X()', (string)$constructor('X'));
	    $this->assertEquals('X(y)', (string)$constructor('X', $var('y')));
	    $this->assertEquals('x.y', (string)$property($var('x'), 'y'));
    }
}