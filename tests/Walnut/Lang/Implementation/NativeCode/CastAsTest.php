<?php

namespace Walnut\Lang\Implementation\NativeCode;

use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
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

final class CastAsTest extends BaseProgramTestHelper {

	private function callCastAs(Value $value, Type $type, Value $expected): void {
		$this->testMethodCall(
			$this->expressionRegistry->constant($value),
			'as',
			$this->expressionRegistry->constant(
				$this->valueRegistry->type($type)
			),
			$expected
		);
	}

	public function testCastAs(): void {
		$this->callCastAs(
			$this->valueRegistry->integer(1),
			$this->typeRegistry->boolean(),
			$this->valueRegistry->true()
		);
		$this->callCastAs(
			$this->valueRegistry->integer(0),
			$this->typeRegistry->boolean(),
			$this->valueRegistry->false()
		);

		$this->builder->typeBuilder()['addEnumeration']('OrderStatus', ['Invalid', 'Draft', 'Completed']);
		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->enumeration(new TypeNameIdentifier('OrderStatus')),
			'asBoolean',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->boolean(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Invalid')
								)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->false()
							)
						),
						$this->builder->expressionRegistry()['matchDefault'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->true()
							)
						),
					]
				),
			)
		);
		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Draft')
			),
			$this->typeRegistry->boolean(),
			$this->valueRegistry->true()
		);
		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Invalid')
			),
			$this->typeRegistry->boolean(),
			$this->valueRegistry->false()
		);

		$this->builder->methodBuilder()['addMethod'](
			$enumType = $this->typeRegistry->enumeration(new TypeNameIdentifier('OrderStatus')),
			'asInteger',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->integer(0, 2),
			$toInt = $this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Invalid')
								)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(0)
							)
						),
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Draft')
								)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(1)
							)
						),
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Completed')
								)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(2)
							)
						)
					],
				),
			)
		);
		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Invalid')
			),
			$this->typeRegistry->integer(),
			$this->valueRegistry->integer(0)
		);
		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Draft')
			),
			$this->typeRegistry->integer(),
			$this->valueRegistry->integer(1)
		);
		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Completed')
			),
			$this->typeRegistry->integer(),
			$this->valueRegistry->integer(2)
		);


		$this->builder->methodBuilder()['addMethod'](
			$this->typeRegistry->integer(),
			'as' . $enumType->name(),
			$this->typeRegistry->null(),
			null,
			$enumType,
			$fromInt = $this->expressionRegistry->functionBody(
				$this->expressionRegistry->match(
					$this->expressionRegistry->variableName(
						new VariableNameIdentifier('$')
					),
					new MatchExpressionEquals(),
					[
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(0)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Invalid')
								)
							),
						),
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(1)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Draft')
								)
							),
						),
						$this->builder->expressionRegistry()['matchPair'](
							$this->expressionRegistry->constant(
								$this->valueRegistry->integer(2)
							),
							$this->expressionRegistry->constant(
								$this->valueRegistry->enumerationValue(
									new TypeNameIdentifier('OrderStatus'),
									new EnumValueIdentifier('Completed')
								)
							),
						)
					],
				),
			)
		);

		$this->callCastAs(
			$this->valueRegistry->integer(0),
			$enumType,
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Invalid')
			),
		);
		$this->callCastAs(
			$this->valueRegistry->integer(1),
			$enumType,
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Draft')
			),
		);
		$this->callCastAs(
			$this->valueRegistry->integer(2),
			$enumType,
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Completed')
			),
		);

		$j = new TypeNameIdentifier('JsonValue');
		$aliasType = $this->typeRegistry->proxyType($j);
	    //$jsonValueType =
		$this->typeRegistry->addAlias($j,
		    $this->typeRegistry->union([
				$this->typeRegistry->null(),
			    $this->typeRegistry->boolean(),
			    $this->typeRegistry->integer(),
			    $this->typeRegistry->real(),
			    $this->typeRegistry->string(),
			    $this->typeRegistry->array($aliasType),
			    $this->typeRegistry->map($aliasType),
			    $this->typeRegistry->result($this->typeRegistry->nothing(), $aliasType),
			    $this->typeRegistry->mutable($aliasType)
		    ], false),
	    );


		//$this->callCastAs(
		//	$this->valueRegistry->string("1"),
			$jv = $this->typeRegistry->alias(new TypeNameIdentifier('JsonValue'));//,
		//	$this->valueRegistry->string("1")
		//);

		$this->builder->methodBuilder()['addMethod'](
			$enumType,
			'asJsonValue',
			$this->typeRegistry->null(),
			null,
			$this->typeRegistry->integer(0, 2),
			$toInt
		);

		$this->callCastAs(
			$this->valueRegistry->enumerationValue(
				new TypeNameIdentifier('OrderStatus'),
				new EnumValueIdentifier('Draft')
			),
			$this->typeRegistry->alias(new TypeNameIdentifier('JsonValue')),
			$this->valueRegistry->integer(1)
		);

		$this->builder->methodBuilder()['addMethod'](
			$jv,
			'as' . $enumType->name(),
			$this->typeRegistry->null(),
			null,
			$enumType,
			$fromInt
		);

		$call = $this->expressionRegistry->methodCall(
			$this->expressionRegistry->variableName(new VariableNameIdentifier('x')),
			new MethodNameIdentifier('as'),
			$this->expressionRegistry->constant(
				$this->valueRegistry->type($enumType)
			)
		);
		$call->analyse(VariableScope::fromPairs(
			new VariablePair(
				new VariableNameIdentifier('x'),
				$jv
			)
		));
		$this->assertTrue(
			$call
				->execute(VariableValueScope::fromPairs(
					new VariableValuePair(
						new VariableNameIdentifier('x'),
						new TypedValue(
							$jv,
							$this->valueRegistry->integer(1)
						)
				)))
				->value()->equals(
					$this->valueRegistry->enumerationValue(
						new TypeNameIdentifier('OrderStatus'),
						new EnumValueIdentifier('Draft')
					)
				)
		);

	}
}
