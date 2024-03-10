<?php

namespace Walnut\Lang\Test\Implementation\Registry;

use RuntimeException;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\Program;
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
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
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
use Walnut\Lang\Blueprint\Value\SubtypeValue;
use Walnut\Lang\Blueprint\Value\TypeValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class ProgramBuilderTemplateTest extends BaseProgramTestHelper {

	private function executeMain(Program $program, string ... $values): string {
		$result = $program->callFunction(
			new VariableNameIdentifier('main'),
			$this->typeRegistry->array(
                $this->typeRegistry->string()
			),
			$this->typeRegistry->string(),
			$this->valueRegistry->list(array_map(
				fn(string $value) => $this->valueRegistry->string($value),
				$values
			))
		);
		while ($result instanceof SubtypeValue) {
			$result = $result->baseValue();
		}
		return $result instanceof StringValue ?
			$result->literalValue() :
			throw new RuntimeException(
				sprintf("Invalid result type: '%s'. String expected", $result::class)
			);
	}

	public function testTemplate(): void {
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
		$addSubtype("PositiveInteger", $Integer(), $functionBody(
			$constant($null())
			/*$matchValue($var('#'), [
				$matchType($Integer(), $return($var('#'))),
				$matchTrue($return($integer(0)))
			])*/
		), null);

		$myVar = $assign("myVar", $record([
			'boolean' => $constant($true()),
			'real' => $constant($real(3.14)),
			'integer' => $constant($integer(42)),
			'string' => $constant($string("Hello")),
			'null' => $constant($null()),
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

		$mainFn = $function($Any(), $Any(), $functionBody(
			$sequence([
				$myVar,
				$property($myVar, "input"),
				//$property($myVar, "integer"),
			])
		));
		$mainFn->analyse();
		$result = $mainFn->execute($false());
		$this->assertEquals($false(), $result);

		$mainFn = $function($Any(), $Any(), $functionBody(
			$sequence([
				$myVar,
				$call($property(
					$property($myVar, "others"),
					"2"
				), $constant($null())),
			])
		));
		$mainFn->analyse();
		$result = $mainFn->execute($false());
		$this->assertEquals($string("Test"), $result);

		$mainFn = $function($Any(), $Any(), $functionBody(
			$sequence([
				$myVar,
				$property(
					$property($myVar, "others"),
					"3"
				),
			])
		));
		$mainFn->analyse();
		$result = $mainFn->execute($false());
		$this->assertEquals($subtype("PositiveInteger", $integer("333")), $result);

		$mainFn = $function($Any(), $Any(), $functionBody(
			$sequence([
				$myVar,
				$method(
					$property($myVar, "others"),
					"length",
					$constant($null())
				),
			])
		));
		$mainFn->analyse();
		$result = $mainFn->execute($false());
		$this->assertEquals($integer(4), $result);


		$addMethod(
			$Array($Any(), 0, PlusInfinity::value),
			new MethodNameIdentifier("myMethod"),
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
					"myMethod",
					$constant($string("Hello"))
				),
			])
		));
		$mainFn->analyse();
		$addVariable('main', $mainFn);

		$program = $this->builder->build();
		$result = $this->executeMain($program, "hello");
		$this->assertEquals("Hello", $result);

		$mainFn = $function($Any(), $Any(), $functionBody(
			$sequence([
				$myVar,
				$method(
					$property($myVar, "others"),
					"nonExistingMethod",
					$constant($true())
				),
			])
		));
		$this->expectException(AnalyserException::class);
		$mainFn->analyse();

		/*
		$this->expectException(AnalyserException::class);
		$this->customMethodRegistryBuilder->addMethod(
			$Array($Any(), 0, PlusInfinity::value),
			new MethodNameIdentifier("myMethod"),
			$String(),
			null,
			$Integer(),
			$functionBody(
				$var('#')
			)
		);*/
	}

	public function testIncluded(): void {
		$this->testWith(__DIR__ . "/program1.php", "Hello", "hello");
	}

	public function testErrorValues(): void {
		$this->testWith(__DIR__ . "/error.php", "w are you?");
	}

	public function testCast7(): void {
		$this->typeRegistry->addAtom(new TypeNameIdentifier('NotANumber'));
		$this->typeRegistry->addState(
			new TypeNameIdentifier('IndexOutOfRange'),
			$this->typeRegistry->record([
				'index' => $this->typeRegistry->integer()
			])
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('CastNotAvailable'),
			$this->typeRegistry->record([
				'from' => $this->typeRegistry->type($this->typeRegistry->any()),
				'to' => $this->typeRegistry->type($this->typeRegistry->any()),
			]),
		);
		$this->testWith(__DIR__ . "/cast7.php", "'Hi'");
		$this->testWith(__DIR__ . "/cast7.php", "'8'", "Welcome!");
		$this->testWith(__DIR__ . "/cast7.php", "25", "12", "13");
		$this->testWith(__DIR__ . "/cast7.php", "@NotANumber[]", "12", "wrong");
		$this->testWith(__DIR__ . "/cast7.php", "23.14", "3.14", "15", "20");
		$this->testWith(__DIR__ . "/cast7.php", "@NotANumber[]", "3.14", "15", "wrong");
	}

	public function testCast9(): void {
		$this->typeRegistry->addAtom(new TypeNameIdentifier('NotANumber'));
		$this->testWith(__DIR__ . "/cast9.php", "@'Please provide just one argument'");
		$this->testWith(__DIR__ . "/cast9.php", "@'Please provide a positive number'", "-30");
		$this->testWith(__DIR__ . "/cast9.php", "'fizz'", "6");
		$this->testWith(__DIR__ . "/cast9.php", "'buzz'", "10");
		$this->testWith(__DIR__ . "/cast9.php", "'fizzbuzz'", "15");
		$this->testWith(__DIR__ . "/cast9.php", "'23'", "23");
	}

	public function testCast18(): void {
		$this->testWith(__DIR__ . "/cast18.php", "w are you?");
	}

	public function testCast108(): void {
		$this->testWith(__DIR__ . "/cast108.php", "Hello!");
	}

	public function testCast109(): void { //map
		$this->testWith(__DIR__ . "/cast109.php", "[2, 6, 16]");
	}

	public function testCast110(): void { //subtypes
		$this->testWith(__DIR__ . "/cast110.php", "[@NotAnEvenNumber[], EvenNumber{8}]");
	}

	public function testCast111(): void { //implement a function type
		$this->testWith(__DIR__ . "/cast111.php", "@'Please provide a number'");
		$this->testWith(__DIR__ . "/cast111.php", "false", 5);
		$this->testWith(__DIR__ . "/cast111.php", "true", 8);
	}

	public function testCast112(): void { //provide config
		$this->testWith(__DIR__ . "/cast112.php", "[host: 'localhost', database: 'db', username: 'user', password: 'pass']");
	}

	public function testCast113(): void { //json stringify
		$this->testWith(__DIR__ . "/cast113.php", "'[\"Hello\",\"World\"]'", "Hello", "World");
	}

	public function testCast114(): void { //dehydrate
		$this->testWith(__DIR__ . "/cast114.php", "[['Hello', 'World'], 2, 'cl', [1, true, null]]", "Hello", "World");
	}

	public function testCast115(): void { //inner scope
		$this->testWith(__DIR__ . "/cast115.php", '[1, 2]');
		$this->testWith(__DIR__ . "/cast115.php", '[3, 3]', '3');
		$this->testWith(__DIR__ . "/cast115.php", '[1, 2]', 'true');
	}

	public function testCast116(): void { //inner scope
		$this->testWith(__DIR__ . "/cast116.php", "[Mutable[String, '4'], type{Real}, @Mutable[String, '4']]");
		$this->testWith(__DIR__ . "/cast116.php", "[Mutable[String, '12'], type{Real}, @Mutable[String, '12']]", 'Hello World!');
	}

	public function testCast117(): void { //convert tuple to record
		$this->testWith(__DIR__ . "/cast117.php", "[a: 'Hi', b: 42, c: true]");
	}

	public function testCast118(): void { //convert tuple to record
		$this->testWith(__DIR__ . "/cast118.php", '35');
	}

	public function testCast119(): void { //convert tuple to record
		$this->testWith(__DIR__ . "/cast119.php",
			"[DatabaseConnection[dsn: 'sqlite:db.sqlite'], " .
			"DatabaseConnector[connection: DatabaseConnection[dsn: 'sqlite:db.sqlite']], 0, " .
			"@DatabaseQueryFailure[query: 'CREATE TABLE projects (id integer PRIMARY KEY, name string)', boundParameters: [], " .
			"error: 'SQLSTATE[HY000]: General error: 1 table projects already exists']" .
			", [[id: 1, name: 'Project 1'], [id: 2, name: 'Project 22']]]"
		);
	}

	public function testCast120(): void { //container instanceOf
		$this->testWith(__DIR__ . "/cast120.php",
			"[[host: 'localhost', database: 'db', username: 'user', password: 'pass'], " .
			"@DependencyContainerError[targetType: type{String}, errorMessage: 'Unsupported type']]");
	}

	public function testCast121(): void { //custom methods as constructors
		$this->testWith(__DIR__ . "/cast121.php",
			"[Book[isbn: '978-3-16-148410-0', data: [author: 'John Doe', title: 'My Book', year: 2021, pages: 123, price: 12.34]], " .
			"BookX[isbn: '978-3-16-148410-0', data: [author: 'new author', title: 'new title', year: 2023, pages: 100, price: 9.99]]]");
	}

	public function testCast122(): void { //custom methods as constructors
		$this->testWith(__DIR__ . "/cast122.php",
			"[[x: 123, y: 'abc', z: true], [x: 123, y: 'abc', z: true], [x: 123, y: 'abc', z: true, w: 123], [x: 123, y: 'abc', z: true, w: 123]]");
	}

	private function testWith(string $path, string $expected, string ... $parameters): void {
		$this->addCoreToContext();

		$functions = $this->builder->typeBuilder();
		extract($functions);

		$functions = $this->builder->typeRegistry();
		extract($functions);

		$functions = $this->builder->valueBuilder();
		extract($functions);

		$functions = $this->builder->valueRegistry();
		extract($functions);

		$functions = $this->builder->expressionRegistry();
		extract($functions);

		$functions = $this->builder->methodBuilder();
		extract($functions);

		require $path;

		$result = $this->executeMain($this->builder->build(), ...$parameters);
		$this->assertEquals($expected, $result);
	}

}