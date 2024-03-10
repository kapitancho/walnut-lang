<?php

namespace Walnut\Lang\Implementation\NativeCode\JsonValue;

use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\MatchExpressionEquals;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class HydrateAsTest extends BaseProgramTestHelper {

	private function callHydrateAs(Value $value, Type $type, Value $expected): void {
		$this->testMethodCall(
			$this->expressionRegistry->methodCall(
				$this->expressionRegistry->constant($value),
				new MethodNameIdentifier('as'),
				$this->expressionRegistry->constant($this->valueRegistry->type(
					$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue'))
				))
			),
			'hydrateAs',
			$this->expressionRegistry->constant($this->valueRegistry->type($type)),
			$expected
		);
	}

	private function callHydrateAsError(Value $value, Type $type, string $expected): void {
		$target = $this->expressionRegistry->methodCall(
			$this->expressionRegistry->constant($value),
			new MethodNameIdentifier('as'),
			$this->expressionRegistry->constant($this->valueRegistry->type(
				$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue'))
			))
		);
		$call = $this->expressionRegistry->methodCall(
			$target,
			new MethodNameIdentifier('hydrateAs'),
			$this->expressionRegistry->constant($this->valueRegistry->type($type))
		);
		$call->analyse(VariableScope::empty());
		$this->assertEquals($expected, (string)
			$call->execute(VariableValueScope::empty())->value()
		);
	}

    private function analyseCallHydrateAs(Type $type): void {
        $this->testMethodCallAnalyse(
			$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
	        'hydrateAs',
            $this->typeRegistry->type($type),
            $this->typeRegistry->result(
				$type, $this->typeRegistry->withName(new TypeNameIdentifier("HydrationError"))
            )
        );
    }

	public function testHydrateAs(): void {
		$this->addCoreToContext();

		$this->typeRegistry->addAtom(
			new TypeNameIdentifier('MyAtom'),
		);

		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->withName(new TypeNameIdentifier('MyAtom')),
			'asJsonValue',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->integer(2)
				)
			)
		);

		$this->typeRegistry->addEnumeration(
			new TypeNameIdentifier('MyEnum'),[
				new EnumValueIdentifier('A'),
				new EnumValueIdentifier('B'),
				new EnumValueIdentifier('C'),
				new EnumValueIdentifier('D')
			]
		);

		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('MyAlias'),
			$this->typeRegistry->integer(1, 5)
		);

		$this->typeRegistry->addState(
			new TypeNameIdentifier('MyState'),
			$this->typeRegistry->integer(),
		);

		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier('MySubtype'),
			$this->typeRegistry->integer(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->null())
			),
			null
		);

		$this->typeRegistry->addEnumeration(
			new TypeNameIdentifier('MyCustomEnum'),[
				new EnumValueIdentifier('A'),
				new EnumValueIdentifier('B'),
				new EnumValueIdentifier('C'),
			]
		);

		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
			'asMyCustomEnum',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyCustomEnum')),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->string('A')
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('MyCustomEnum'),
									new EnumValueIdentifier('A')
								)
							)
						),
						$this->builder->expressionRegistry()['matchDefault'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('MyCustomEnum'),
									new EnumValueIdentifier('B')
								)
							)
						),
					]
				),
			)
		);

		$this->typeRegistry->addState(
			new TypeNameIdentifier('MyCustomState'),
			$this->typeRegistry->string(),
		);

		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
			'asMyCustomState',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->state(new TypeNameIdentifier('MyCustomState')),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->string('A')
							),
							$this->expressionRegistry->constructorCall(
								new TypeNameIdentifier('MyCustomState'),
								$this->expressionRegistry->constant(
									$this->valueRegistry->string('A')
								)
							)
						),
						$this->builder->expressionRegistry()['matchDefault'](
							$this->expressionRegistry->constructorCall(
								new TypeNameIdentifier('MyCustomState'),
								$this->expressionRegistry->constant(
									$this->valueRegistry->string('B')
								)
							)
						),
					]
				),
			)
		);

		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier('MyCustomSubtype'),
			$this->typeRegistry->string(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->null())
			),
			null
		);

		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue')),
			'asMyCustomSubtype',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->subtype(new TypeNameIdentifier('MyCustomSubtype')),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->string('A')
							),
							$this->expressionRegistry->constructorCall(
								new TypeNameIdentifier('MyCustomSubtype'),
								$this->expressionRegistry->constant(
									$this->valueRegistry->string('A')
								)
							)
						),
						$this->builder->expressionRegistry()['matchDefault'](
							$this->expressionRegistry->constructorCall(
								new TypeNameIdentifier('MyCustomSubtype'),
								$this->expressionRegistry->constant(
									$this->valueRegistry->string('B')
								)
							)
						),
					]
				),
			)
		);

		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier('MyConstructorSubtype'),
			$this->typeRegistry->integer(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->constant($this->valueRegistry->integer(1)),
					new MatchExpressionEquals, [
						new MatchExpressionPair(
							$this->expressionRegistry->methodCall(
								$this->expressionRegistry->variableName(
									new VariableNameIdentifier('#')
								),
								new MethodNameIdentifier('binaryModulo'),
								$this->expressionRegistry->constant($this->valueRegistry->integer(2))
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->error(
									$this->valueRegistry->atom(new TypeNameIdentifier('NotANumber'))
								)
							),
						),
						new MatchExpressionDefault(
							$this->expressionRegistry->constant($this->valueRegistry->null())
						)
					]
				)
			),
			$this->typeRegistry->withName(new TypeNameIdentifier('NotANumber'))
		);


		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier('MyNestedSubtype'),
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->boolean(),
				'b' => $this->typeRegistry->withName(new TypeNameIdentifier('MySubtype'))
			]),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->null())
			),
			null
		);


		$this->analyseCallHydrateAs($this->typeRegistry->integer());
		$this->analyseCallHydrateAs(
			$this->typeRegistry->withName(new TypeNameIdentifier('MySubtype')),
		);
		$this->analyseCallHydrateAs(
			$this->typeRegistry->withName(new TypeNameIdentifier('MyNestedSubtype')),
		);

		//Integer
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->integer(),
			$this->valueRegistry->integer(123)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->integer(1, 100),
			"@HydrationError[value: 123, hydrationPath: 'value', errorMessage: 'The integer value should be in the range 1..100']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->integer(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be an integer in the range -Infinity..+Infinity']"
		);
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->integerSubset([
				$this->valueRegistry->integer(1),
				$this->valueRegistry->integer(5),
				$this->valueRegistry->integer(123),
			]),
			$this->valueRegistry->integer(123)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->integerSubset([
				$this->valueRegistry->integer(1),
				$this->valueRegistry->integer(5),
			]),
			"@HydrationError[value: 123, hydrationPath: 'value', errorMessage: 'The integer value should be among 1, 5']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->integerSubset([
				$this->valueRegistry->integer(1),
				$this->valueRegistry->integer(5),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be an integer among 1, 5']"
		);

		//Real
		$this->callHydrateAs(
			$this->valueRegistry->real(12.3),
			$this->typeRegistry->real(),
			$this->valueRegistry->real(12.3)
		);
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->real(),
			$this->valueRegistry->real(123)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->real(12.3),
			$this->typeRegistry->real(1, 9.99),
			"@HydrationError[value: 12.3, hydrationPath: 'value', errorMessage: 'The real value should be in the range 1..9.99']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->real(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a real number in the range -Infinity..+Infinity']"
		);
		$this->callHydrateAs(
			$this->valueRegistry->real(12.3),
			$this->typeRegistry->realSubset([
				$this->valueRegistry->real(1),
				$this->valueRegistry->real(3.14),
				$this->valueRegistry->real(12.3),
			]),
			$this->valueRegistry->real(12.3)
		);
		$this->callHydrateAs(
			$this->valueRegistry->integer(1),
			$this->typeRegistry->realSubset([
				$this->valueRegistry->real(1),
				$this->valueRegistry->real(3.14),
				$this->valueRegistry->real(12.3),
			]),
			$this->valueRegistry->real(1)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->real(12.3),
			$this->typeRegistry->realSubset([
				$this->valueRegistry->real(1),
				$this->valueRegistry->real(3.14),
			]),
			"@HydrationError[value: 12.3, hydrationPath: 'value', errorMessage: 'The real value should be among 1, 3.14']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->realSubset([
				$this->valueRegistry->real(1),
				$this->valueRegistry->real(3.14),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a real number among 1, 3.14']"
		);

		//String
		$this->callHydrateAs(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->string(),
			$this->valueRegistry->string('hello')
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->string(10, 100),
			"@HydrationError[value: 'hello', hydrationPath: 'value', errorMessage: 'The string value should be with a length between 10 and 100']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->string(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a string with a length between 0 and +Infinity']"
		);
		$this->callHydrateAs(
			$this->valueRegistry->string('hi!'),
			$this->typeRegistry->stringSubset([
				$this->valueRegistry->string('hello'),
				$this->valueRegistry->string('world'),
				$this->valueRegistry->string('hi!'),
			]),
			$this->valueRegistry->string('hi!')
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hi!'),
			$this->typeRegistry->stringSubset([
				$this->valueRegistry->string('hello'),
				$this->valueRegistry->string('world'),
			]),
			"@HydrationError[value: 'hi!', hydrationPath: 'value', errorMessage: 'The string value should be among \\`hello\\`, \\`world\\`']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->stringSubset([
				$this->valueRegistry->string('hello'),
				$this->valueRegistry->string('world'),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a string among \\`hello\\`, \\`world\\`']"
		);

		//Boolean
		$this->callHydrateAs(
			$this->valueRegistry->true(),
			$this->typeRegistry->boolean(),
			$this->valueRegistry->true()
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->boolean(),
			"@HydrationError[value: 'hello', hydrationPath: 'value', errorMessage: 'The value should be a boolean']"
		);

		//True
		$this->callHydrateAs(
			$this->valueRegistry->true(),
			$this->typeRegistry->true(),
			$this->valueRegistry->true()
		);
		$this->callHydrateAsError(
			$this->valueRegistry->false(),
			$this->typeRegistry->true(),
			"@HydrationError[value: false, hydrationPath: 'value', errorMessage: 'The boolean value should be true']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->true(),
			"@HydrationError[value: 'hello', hydrationPath: 'value', errorMessage: 'The value should be \\`true\\`']"
		);

		//False
		$this->callHydrateAs(
			$this->valueRegistry->false(),
			$this->typeRegistry->false(),
			$this->valueRegistry->false()
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->false(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The boolean value should be false']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->false(),
			"@HydrationError[value: 'hello', hydrationPath: 'value', errorMessage: 'The value should be \\`false\\`']"
		);

		//Null
		$this->callHydrateAs(
			$this->valueRegistry->null(),
			$this->typeRegistry->null(),
			$this->valueRegistry->null()
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('hello'),
			$this->typeRegistry->null(),
			"@HydrationError[value: 'hello', hydrationPath: 'value', errorMessage: 'The value should be \\`null\\`']"
		);

		//Array
		if (0) $this->callHydrateAs(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->array(),
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
		);
		if (0) $this->callHydrateAsError(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->array($this->typeRegistry->any(), 10, 100),
			"@HydrationError[value: [42, 'Hello'], hydrationPath: 'value', errorMessage: 'The array value should be with a length between 10 and 100']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->array(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be an array with a length between 0 and +Infinity']"
		);
		if (0) $this->callHydrateAsError(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->array($this->typeRegistry->integer()),
			"@HydrationError[value: 'Hello', hydrationPath: 'value'[1], errorMessage: 'The value should be an integer in the range -Infinity..+Infinity']"
		);

		//Map
		$this->callHydrateAs(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->map(),
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
		);
		$this->callHydrateAsError(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->map($this->typeRegistry->any(), 10, 100),
			"@HydrationError[value: [a: 42, b: 'Hello'], hydrationPath: 'value', errorMessage: 'The map value should be with a length between 10 and 100']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->map(),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a map with a length between 0 and +Infinity']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->map($this->typeRegistry->integer()),
			"@HydrationError[value: 'Hello', hydrationPath: 'value.b', errorMessage: 'The value should be an integer in the range -Infinity..+Infinity']"
		);

		//Tuple
		if (0) $this->callHydrateAs(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->tuple([
				$this->typeRegistry->integer(),
				$this->typeRegistry->string(),
			]),
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
		);
		if (0) $this->callHydrateAsError(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->tuple([
				$this->typeRegistry->any(),
				$this->typeRegistry->string(),
				$this->typeRegistry->boolean(),
			]),
			"@HydrationError[value: [42, 'Hello'], hydrationPath: 'value', errorMessage: 'The tuple value should be with 3 items']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->tuple([]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a tuple with 0 items']"
		);
		if (0) $this->callHydrateAsError(
			$this->valueRegistry->list([
				$this->valueRegistry->integer(42),
				$this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->tuple([
				$this->typeRegistry->integer(),
				$this->typeRegistry->string(10, 100),
			]),
			"@HydrationError[value: 'Hello', hydrationPath: 'value[1]', errorMessage: 'The string value should be with a length between 10 and 100']"
		);

		//Map
		$this->callHydrateAs(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->integer(),
				'b' => $this->typeRegistry->string(),
			]),
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
		);
		$this->callHydrateAsError(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->any(),
				'b' => $this->typeRegistry->string(),
				'c' => $this->typeRegistry->boolean(),
			]),
			"@HydrationError[value: [a: 42, b: 'Hello'], hydrationPath: 'value', errorMessage: 'The record value should be with 3 items']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->any(),
				'c' => $this->typeRegistry->boolean(),
			]),
			"@HydrationError[value: [a: 42, b: 'Hello'], hydrationPath: 'value', errorMessage: 'The record value should contain the key c']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->record([]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a record with 0 items']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(42),
				'b' => $this->valueRegistry->string("Hello"),
			]),
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->integer(),
				'b' => $this->typeRegistry->string(10, 100),
			]),
			"@HydrationError[value: 'Hello', hydrationPath: 'value.b', errorMessage: 'The string value should be with a length between 10 and 100']"
		);

		//Alias
		$this->callHydrateAs(
			$this->valueRegistry->integer(3),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyAlias')),
			$this->valueRegistry->integer(3)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyAlias')),
			"@HydrationError[value: 123, hydrationPath: 'value', errorMessage: 'The integer value should be in the range 1..5']"
		);

		//Union
		$this->callHydrateAs(
			$this->valueRegistry->integer(3),
			$this->typeRegistry->union([
				$this->typeRegistry->integer(),
				$this->typeRegistry->string(),
			]),
			$this->valueRegistry->integer(3)
		);
		$this->callHydrateAs(
			$this->valueRegistry->integer(3),
			$this->typeRegistry->union([
				$this->typeRegistry->string(),
				$this->typeRegistry->integer(),
			]),
			$this->valueRegistry->integer(3)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->boolean(true),
			$this->typeRegistry->union([
				$this->typeRegistry->string(),
				$this->typeRegistry->integer(),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a string with a length between 0 and +Infinity']"
		);

		//Intersection
		//TODO
		/*$this->callHydrateAsError(
			$this->valueRegistry->boolean(true),
			$this->typeRegistry->intersection([
				$this->typeRegistry->record(['a' => $this->typeRegistry->string()]),
				$this->typeRegistry->record(['b' => $this->typeRegistry->integer()]),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a record with 1 items]"
		);*/

		//Result
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->result($this->typeRegistry->integer(), $this->typeRegistry->string()),
			$this->valueRegistry->integer(123)
		);
		$this->callHydrateAs(
			$this->valueRegistry->string("hello"),
			$this->typeRegistry->result($this->typeRegistry->integer(), $this->typeRegistry->string()),
			$this->valueRegistry->error($this->valueRegistry->string("hello"))
		);
		$this->callHydrateAsError(
			$this->valueRegistry->boolean(true),
			$this->typeRegistry->result($this->typeRegistry->integer(), $this->typeRegistry->string()),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be an integer in the range -Infinity..+Infinity']"
		);

		//Mutable
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->mutable($this->typeRegistry->integer()),
			$this->valueRegistry->mutable(
				$this->typeRegistry->integer(),
				$this->valueRegistry->integer(123)
			)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->mutable($this->typeRegistry->integer()),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be an integer in the range -Infinity..+Infinity']"
		);

		//Type
		$this->callHydrateAs(
			$this->valueRegistry->string("MyState"),
			$this->typeRegistry->type($this->typeRegistry->any()),
			$this->valueRegistry->type($this->typeRegistry->withName(new TypeNameIdentifier('MyState')))
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string("MyState"),
			$this->typeRegistry->type($this->typeRegistry->integer()),
			"@HydrationError[value: 'MyState', hydrationPath: 'value', errorMessage: 'The type should be a subtype of Integer']"
		);
		if (0) $this->callHydrateAsError(
			$this->valueRegistry->string("InvalidTypeName"),
			$this->typeRegistry->type($this->typeRegistry->integer()),
			"@HydrationError[value: InvalidTypeName, hydrationPath: 'value', errorMessage: 'The string value should be a name of a valid type']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->type($this->typeRegistry->integer()),
			"@HydrationError[value: 123, hydrationPath: 'value', errorMessage: 'The value should be a string, containing a name of a valid type']"
		);

		//Atom
		$this->callHydrateAs(
			$this->valueRegistry->integer(42),
			$this->typeRegistry->atom(new TypeNameIdentifier('MyAtom')),
			$this->valueRegistry->atom(new TypeNameIdentifier('MyAtom')),
		);
		$this->callHydrateAs(
			$this->valueRegistry->integer(42),
			$this->typeRegistry->union([
				$this->typeRegistry->string(),
				$this->typeRegistry->atom(new TypeNameIdentifier('MyAtom'))
			]),
			$this->valueRegistry->atom(new TypeNameIdentifier('MyAtom')),
		);

		//Enumeration
		$this->callHydrateAs(
			$this->valueRegistry->string('C'),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyEnum')),
			$this->valueRegistry->enumerationValue(new TypeNameIdentifier('MyEnum'), new EnumValueIdentifier('C')),
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyEnum')),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a string with a value among MyEnum.A, MyEnum.B, MyEnum.C, MyEnum.D']"
		);
		$this->callHydrateAs(
			$this->valueRegistry->string('C'),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyEnum'))->subsetType([
				new EnumValueIdentifier('A'),
				new EnumValueIdentifier('B'),
				new EnumValueIdentifier('C'),
			]),
			$this->valueRegistry->enumerationValue(new TypeNameIdentifier('MyEnum'), new EnumValueIdentifier('C')),
		);
		$this->callHydrateAsError(
			$this->valueRegistry->string('C'),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyEnum'))->subsetType([
				new EnumValueIdentifier('A'),
				new EnumValueIdentifier('B'),
			]),
			"@HydrationError[value: 'C', hydrationPath: 'value', errorMessage: 'The value should be a string with a value among MyEnum.A, MyEnum.B']"
		);
		$this->callHydrateAsError(
			$this->valueRegistry->true(),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyEnum'))->subsetType([
				new EnumValueIdentifier('A'),
				new EnumValueIdentifier('B'),
			]),
			"@HydrationError[value: true, hydrationPath: 'value', errorMessage: 'The value should be a string with a value among MyEnum.A, MyEnum.B']"
		);

		$this->callHydrateAs(
			$this->valueRegistry->string('A'),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyCustomEnum')),
			$this->valueRegistry->enumerationValue(new TypeNameIdentifier('MyCustomEnum'), new EnumValueIdentifier('A')),
		);
		$this->callHydrateAs(
			$this->valueRegistry->true(),
			$this->typeRegistry->enumeration(new TypeNameIdentifier('MyCustomEnum')),
			$this->valueRegistry->enumerationValue(new TypeNameIdentifier('MyCustomEnum'), new EnumValueIdentifier('B')),
		);

		//State
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyState')),
			$this->valueRegistry->stateValue(
				new TypeNameIdentifier('MyState'),
				$this->valueRegistry->integer(123)
			)
		);

		$this->callHydrateAs(
			$this->valueRegistry->string('A'),
			$this->typeRegistry->state(new TypeNameIdentifier('MyCustomState')),
			$this->valueRegistry->stateValue(new TypeNameIdentifier('MyCustomState'), $this->valueRegistry->string('A')),
		);
		$this->callHydrateAs(
			$this->valueRegistry->true(),
			$this->typeRegistry->state(new TypeNameIdentifier('MyCustomState')),
			$this->valueRegistry->stateValue(new TypeNameIdentifier('MyCustomState'), $this->valueRegistry->string('B')),
		);

		//Subtype
		$this->callHydrateAs(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->withName(new TypeNameIdentifier('MySubtype')),
			$this->valueRegistry->subtypeValue(
				new TypeNameIdentifier('MySubtype'),
				$this->valueRegistry->integer(123)
			)
		);

		$this->callHydrateAs(
			$this->valueRegistry->string('A'),
			$this->typeRegistry->subtype(new TypeNameIdentifier('MyCustomSubtype')),
			$this->valueRegistry->subtypeValue(new TypeNameIdentifier('MyCustomSubtype'), $this->valueRegistry->string('A')),
		);
		$this->callHydrateAs(
			$this->valueRegistry->true(),
			$this->typeRegistry->subtype(new TypeNameIdentifier('MyCustomSubtype')),
			$this->valueRegistry->subtypeValue(new TypeNameIdentifier('MyCustomSubtype'), $this->valueRegistry->string('B')),
		);

		$this->callHydrateAs(
			$this->valueRegistry->integer(124),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyConstructorSubtype')),
			$this->valueRegistry->subtypeValue(
				new TypeNameIdentifier('MyConstructorSubtype'),
				$this->valueRegistry->integer(124)
			)
		);
		$this->callHydrateAsError(
			$this->valueRegistry->integer(123),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyConstructorSubtype')),
			"@HydrationError[value: 123, hydrationPath: 'value', errorMessage: 'Subtype hydration failed: @NotANumber[]']"
		);

		$this->callHydrateAs(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->boolean(true),
				'b' => $this->valueRegistry->integer(123)
			]),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyNestedSubtype')),
			$this->valueRegistry->subtypeValue(
				new TypeNameIdentifier('MyNestedSubtype'),
				$this->valueRegistry->dict([
					'a' => $this->valueRegistry->boolean(true),
					'b' => $this->valueRegistry->subtypeValue(
						new TypeNameIdentifier('MySubtype'),
						$this->valueRegistry->integer(123)
					)
				]),
			)
		);

		$value = '{"a":true,"b":123,"c":[15,20],"d":{"x":"B","y":2}}';

		$result = $this->expressionRegistry->methodCall(
			//$this->expressionRegistry->noError(
				$this->expressionRegistry->methodCall(
					$this->expressionRegistry->constant($this->valueRegistry->string($value)),
					new MethodNameIdentifier('jsonDecode'),
					$this->expressionRegistry->constant($this->valueRegistry->null())
				//)
			),
			new MethodNameIdentifier('hydrateAs'),
			$this->expressionRegistry->constant($this->valueRegistry->type(
				$this->typeRegistry->record([
					'a' => $this->typeRegistry->boolean(),
					'b' => $this->typeRegistry->integer(),
					'c' => $this->typeRegistry->array($this->typeRegistry->withName(
						new TypeNameIdentifier('MyState')
					)),
					'd' => $this->typeRegistry->record([
						'x' => $this->typeRegistry->enumeration(new TypeNameIdentifier('MyCustomEnum')),
						'y' => $this->typeRegistry->atom(new TypeNameIdentifier('MyAtom')),
					]),
				])
			))
		)->execute(VariableValueScope::empty());
		$this->assertEquals(
			'[a: true, b: 123, c: [MyState{15}, MyState{20}], d: [x: MyCustomEnum.B, y: MyAtom[]]]',
			(string)$result->value()
		);
		$back = $this->expressionRegistry->methodCall(
			$this->expressionRegistry->methodCall(
				$this->expressionRegistry->constant($result->value()),
				new MethodNameIdentifier('asJsonValue'),
				$this->expressionRegistry->constant($this->valueRegistry->null())
				/*$this->expressionRegistry->constant($this->valueRegistry->type(
					$this->typeRegistry->withName(new TypeNameIdentifier('JsonValue'))
				))*/
			),
			new MethodNameIdentifier('stringify'),
			$this->expressionRegistry->constant($this->valueRegistry->null())
		)->execute(VariableValueScope::empty());

		$this->assertEquals(
			"'" . $value . "'",
			(string)$back->value()
		);

		$result = $this->expressionRegistry->methodCall(
			$this->expressionRegistry->constant($this->valueRegistry->string('invalid json')),
			new MethodNameIdentifier('jsonDecode'),
			$this->expressionRegistry->constant($this->valueRegistry->null())
		)->execute(VariableValueScope::empty());

		$this->assertEquals(
			"@InvalidJsonString[value: 'invalid json']",
			(string)$result->value()
		);

	}
}
