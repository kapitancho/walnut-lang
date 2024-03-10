<?php

namespace Walnut\Lang\Test\Implementation\Registry;

use LogicException;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\UnresolvableDependency;
use Walnut\Lang\Blueprint\Type\AliasType;
use Walnut\Lang\Blueprint\Type\AnyType;
use Walnut\Lang\Blueprint\Type\AtomType;
use Walnut\Lang\Blueprint\Type\BooleanType;
use Walnut\Lang\Blueprint\Type\FalseType;
use Walnut\Lang\Blueprint\Type\NothingType;
use Walnut\Lang\Blueprint\Type\NullType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\TrueType;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Type\EnumerationType;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class ProgramBuilderTest extends BaseProgramTestHelper {

	public function testValueBuilder(): void {
		$functions = $this->builder->valueBuilder();
		extract($functions);

		$functions = $this->builder->valueRegistry();
		extract($functions);

		$addVariable("foo", $i = $integer(123));
		$this->assertEquals(123, $this->valueRegistry->variables()->valueOf(
			new VariableNameIdentifier("foo")
		)->equals($i));
	}

	public function testMethodWithDependency(): void {
		$functions = $this->builder->typeBuilder();
		extract($functions);

		$functions = $this->builder->valueBuilder();
		extract($functions);

		$functions = $this->builder->typeRegistry();
		extract($functions);

		$functions = $this->builder->expressionRegistry();
		extract($functions);

		$functions = $this->builder->valueRegistry();
		extract($functions);

		$functions = $this->builder->methodBuilder();
		extract($functions);

		$addAlias('MyStringToInteger', $Function($String(), $Integer()));

		$addVariable("myStringToInteger", $function($String(), $Integer(), $functionBody(
			$method($var('#'), "Length", $constant($null()))
		)));

		$addMethod(
			$this->typeRegistry->string(),
			"myFunc",
			$this->typeRegistry->null(),
			$this->typeRegistry->withName(new TypeNameIdentifier('MyStringToInteger')),
			$this->typeRegistry->integer(),
			$functionBody($call($var('%'), $var('$')))
		);

		$t = $method($constant($string('Hello')), "myFunc", $constant($null()));
		$result = $t->execute(VariableValueScope::empty())->value();

		$this->assertEquals($this->valueRegistry->integer(5), $result);
	}

	public function testValueByTypeName(): void {
		$this->addCoreToContext();

		$functions = $this->builder->typeBuilder();
		extract($functions);

		$functions = $this->builder->valueBuilder();
		extract($functions);

		$functions = $this->builder->expressionRegistry();
		extract($functions);

		$functions = $this->builder->typeRegistry();
		extract($functions);

		$functions = $this->builder->valueRegistry();
		extract($functions);

		$functions = $this->builder->methodBuilder();
		extract($functions);

		$addAtom('MyAtom');
		$addEnumeration('MyEnum', ['Foo', 'Bar']);
		$addEnumeration('MySpecialEnum', ['Foo', 'Baz', 'Woo']);
		$addAlias('MyStringToInteger', $Function($String(), $Integer()));

		$addVariable("myStringToInteger", $function($String(), $Integer(), $functionBody(
			$method($var('#'), "Length", $constant($null()))
		)));

		$addAlias('MyStringToReal', $Function($String(), $Real()));
		$addVariable("myStringToReal1", $function($String(), $Real(), $functionBody(
			$constant($real(3.14)))
		));
		$addVariable("myStringToReal2", $function($String(), $Real(), $functionBody(
			$constant($real(-9)))
		));

		$addMethod(
			$this->typeRegistry->atom(new TypeNameIdentifier('DependencyContainer')),
			"asMySpecialEnum",
			$this->typeRegistry->null(),
			$this->typeRegistry->null(),
			$this->typeRegistry->withName(new TypeNameIdentifier('MySpecialEnum')),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->enumerationValue(
					new TypeNameIdentifier('MySpecialEnum'),
					new EnumValueIdentifier('Woo')
				)),
			)
		);

		$this->assertEquals($this->valueRegistry->atom(
			new TypeNameIdentifier('MyAtom')
		), $val($NamedType('MyAtom')));

		$this->assertEquals(UnresolvableDependency::notFound, $val('MyEnum'));

		$this->assertEquals(UnresolvableDependency::ambiguous, $val('MyStringToReal'));

		$this->assertEquals($this->valueRegistry->enumerationValue(
			new TypeNameIdentifier('MySpecialEnum'),
			new EnumValueIdentifier('Woo')
		), $val('MySpecialEnum'));

		$this->assertEquals($this->valueRegistry->variables()
			->valueOf(new VariableNameIdentifier('myStringToInteger')),
			$val('MyStringToInteger'));

		$this->assertEquals(
			"[MySpecialEnum.Woo, ^String => Integer :: #->Length]",
			(string)$val(
				$Tuple([$NamedType('MySpecialEnum'), $NamedType('MyStringToInteger')])
			)
		);
		$this->assertEquals(
			UnresolvableDependency::unsupportedType,
			$val($Tuple([$NamedType('MySpecialEnum'), $Integer()]))
		);

		$this->assertEquals(
			"[foo: MySpecialEnum.Woo, bar: ^String => Integer :: #->Length]",
			(string)$val(
				$Record(["foo" => $NamedType('MySpecialEnum'), "bar" => $NamedType('MyStringToInteger')])
			)
		);
		$this->assertEquals(
			UnresolvableDependency::unsupportedType,
			$val($Record(["foo" => $NamedType('MySpecialEnum'), "bar" => $Integer()]))
		);
	}

	public function testTypeBuilder(): void {
		$functions = $this->builder->typeBuilder();
		extract($functions);

		$functions = $this->builder->typeRegistry();
		extract($functions);

		$functions = $this->builder->valueRegistry();
		extract($functions);

		$barPayment = $addAtom("BarPayment");
		$cardPayment = $addEnumeration("CardPayment", ["Visa", "MasterCard", "AmericanExpress", "Revolut", "PayPal"]);
		$cashPayment = $addAlias("CashPayment", $barPayment);
		$virtualPayment = $addSubtype("VirtualPayment", $cardPayment, $this->expressionRegistry->functionBody(
			$this->expressionRegistry->sequence([
				$this->expressionRegistry->constant($this->valueRegistry->string("paymentId")),
			])
		), null);
		$this->assertInstanceOf(AtomType::class, $barPayment);
		$this->assertInstanceOf(EnumerationType::class, $cardPayment);
		$this->assertInstanceOf(AliasType::class, $cashPayment);
		$this->assertInstanceOf(SubtypeType::class, $virtualPayment);

		$this->assertEquals($Atom("BarPayment"), $barPayment);
		$this->assertEquals($Enumeration("CardPayment"), $cardPayment);
		$this->assertEquals($Alias("CashPayment"), $cashPayment);
		$this->assertEquals($Subtype("VirtualPayment"), $virtualPayment);
		$this->assertEquals($NamedType("VirtualPayment"), $virtualPayment);

		$enumerationSubset = $EnumerationSubset("CardPayment", ["Visa", "PayPal"]);
		$this->assertEquals("Visa", $enumerationSubset->subsetValues()['Visa']->name()->identifier);

		$vEnumeration = $enumeration("CardPayment", "Visa");
		$this->assertEquals("Visa", $vEnumeration->name()->identifier);
		$this->assertEquals("CardPayment", $vEnumeration->enumeration()->name()->identifier);

		$vInteger = $integer(3);
		$vSubtype = $subtype("VirtualPayment", $vInteger);
		$this->assertEquals("VirtualPayment", $vSubtype->type()->name()->identifier);
		$this->assertEquals($vInteger, $vSubtype->baseValue());
	}

	public function testTypeRegistry(): void {
		$functions = $this->builder->typeRegistry();
		extract($functions);

		$this->assertInstanceOf(AnyType::class, $Any());
		$this->assertInstanceOf(NothingType::class, $Nothing());
		$this->assertInstanceOf(BooleanType::class, $Boolean());
		$this->assertInstanceOf(TrueType::class, $True());
		$this->assertInstanceOf(FalseType::class, $False());
		$this->assertInstanceOf(NullType::class, $Null());

		$str = $String(3, 255);
		$this->assertEquals(3, $str->range()->minLength());
		$this->assertEquals(255, $str->range()->maxLength());

		$int = $Integer(3, 255);
		$this->assertEquals(3, $int->range()->minValue());
		$this->assertEquals(255, $int->range()->maxValue());

		$real = $Real(3.14, 255);
		$this->assertEquals(3.14, $real->range()->minValue());
		$this->assertEquals(255, $real->range()->maxValue());

		$array = $Array($str, 3, 255);
		$this->assertEquals($str, $array->itemType());
		$this->assertEquals(3, $array->range()->minLength());
		$this->assertEquals(255, $array->range()->maxLength());

		$map = $Map($str, 3, 255);
		$this->assertEquals($str, $map->itemType());
		$this->assertEquals(3, $map->range()->minLength());
		$this->assertEquals(255, $map->range()->maxLength());

		$tuple = $Tuple([$str, $int, $real]);
		$this->assertEquals($int, $tuple->types()[1]);

		$record = $Record(["foo" => $str, "bar" => $int, "baz" => $real]);
		$this->assertEquals($int, $record->types()["bar"]);

		$function = $Function($str, $int);
		$this->assertEquals($str, $function->parameterType());
		$this->assertEquals($int, $function->returnType());

		$type = $Type($str);
		$this->assertEquals($str, $type->refType());

		$mutable = $Mutable($str);
		$this->assertEquals($str, $mutable->valueType());

		$union = $Union([$str, $int, $real]);
		$this->assertEquals($int, $union->types()[1]);

		$intersection = $Intersection([$str, $int, $real]);
		$this->assertEquals($int, $intersection->types()[1]);

		$integerSubset = $IntegerSubset([3, 255]);
		$this->assertEquals(3, $integerSubset->subsetValues()[0]->literalValue());

		$realSubset = $RealSubset([3.14, 255]);
		$this->assertEquals(3.14, $realSubset->subsetValues()[0]->literalValue());

		$stringSubset = $StringSubset(["Hello", "World"]);
		$this->assertEquals("Hello", $stringSubset->subsetValues()[0]->literalValue());
	}

	public function testValueRegistry(): void {
		$functions = $this->builder->valueRegistry();
		extract($functions);

		$vInteger = $integer(3);
		$this->assertEquals(3, $vInteger->literalValue());

		$vReal = $real(3.14);
		$this->assertEquals(3.14, $vReal->literalValue());

		$vString = $string("Hello");
		$this->assertEquals("Hello", $vString->literalValue());

		$vBoolean = $boolean(true);
		$this->assertTrue($vBoolean->literalValue());

		$vTrue = $true();
		$this->assertTrue($vTrue->literalValue());

		$vFalse = $false();
		$this->assertFalse($vFalse->literalValue());

		$vNull = $null();
		$this->assertNull($vNull->literalValue());

		$vList = $list([$vInteger, $vReal, $vString]);
		$this->assertEquals($vReal, $vList->values()[1]);

		$vDict = $dict(["foo" => $vInteger, "bar" => $vReal, "baz" => $vString]);
		$this->assertEquals($vReal, $vDict->values()["bar"]);

		$vFunction = $function($this->typeRegistry->any(), $this->typeRegistry->nothing(), $this->expressionRegistry->functionBody(
			$this->expressionRegistry->sequence([
				$this->expressionRegistry->constant($this->valueRegistry->string("Hello")),
			])
		));
		$this->assertEquals($this->typeRegistry->any(), $vFunction->parameterType());
		$this->assertEquals($this->typeRegistry->nothing(), $vFunction->returnType());
		$this->assertEquals("Hello", $vFunction->body()->expression()->expressions()[0]->value()->literalValue());

		$vMutable = $mutable($this->typeRegistry->any(), $vInteger);
		$this->assertEquals($this->typeRegistry->any(), $vMutable->targetType());
		$this->assertEquals($vInteger, $vMutable->value());
	}

	public function testExpressionRegistry(): void {
		$functions = $this->builder->valueRegistry();
		extract($functions);

		$functions = $this->builder->expressionRegistry();
		extract($functions);

		$exConstant = $constant($integer(123));
		$this->assertEquals(123, $exConstant->value()->literalValue());

		$exTuple = $tuple([$exConstant, $constant($string("Hello"))]);
		$this->assertEquals("Hello", $exTuple->values()[1]->value()->literalValue());

		$exRecord = $record(["foo" => $exConstant, "bar" => $constant($string("Hello"))]);
		$this->assertEquals("Hello", $exRecord->values()["bar"]->value()->literalValue());

		$exSequence = $sequence([$exConstant, $constant($string("Hello"))]);
		$this->assertEquals("Hello", $exSequence->expressions()[1]->value()->literalValue());

		$exReturn = $return($exConstant);
		$this->assertEquals(123, $exReturn->returnedExpression()->value()->literalValue());

		$exVariableName = $var("foo");
		$this->assertEquals("foo", $exVariableName->variableName()->identifier);

		$exVariableAssignment = $assign("foo", $exConstant);
		$this->assertEquals(123, $exVariableAssignment->assignedExpression()->value()->literalValue());
		$this->assertEquals("foo", $exVariableAssignment->variableName()->identifier);

		$exMatchValue = $matchValue($exConstant, [
			$matchPair($constant($integer(123)), $exConstant),
			$matchPair($constant($integer(456)), $constant($string("Hello"))),
			$matchDefault($constant($real(3.14)))
		]);
		$this->assertEquals(123, $exMatchValue->pairs()[0]->matchExpression->value()->literalValue());
		$this->assertEquals(3.14, $exMatchValue->pairs()[2]->valueExpression->value()->literalValue());

		$exMatchType = $matchType($exConstant, [
			$matchPair($constant($integer(123)), $exConstant),
			$matchPair($constant($integer(456)), $constant($string("Hello"))),
		]);
		$this->assertEquals(123, $exMatchType->pairs()[0]->matchExpression->value()->literalValue());

		$exMatchTrue = $matchTrue([
			$matchPair($constant($integer(123)), $exConstant),
			$matchPair($constant($integer(456)), $constant($string("Hello"))),
		]);

		$this->assertEquals(123, $exMatchTrue->pairs()[0]->matchExpression->target()->value()->literalValue());

		$exMatchIf = $matchIf($exConstant, $constant($integer(456)), $constant($string("Hello")));
		$this->assertInstanceOf(MatchExpressionPair::class, $exMatchIf->pairs()[0]);
		$this->assertTrue($exMatchIf->pairs()[0]->matchExpression->value()->literalValue());
		$this->assertEquals(456, $exMatchIf->pairs()[0]->valueExpression->value()->literalValue());
		$this->assertInstanceOf(MatchExpressionDefault::class, $exMatchIf->pairs()[1]);
		$this->assertEquals("Hello", $exMatchIf->pairs()[1]->valueExpression->value()->literalValue());

		$exCall = $call($exConstant, $exConstant);
		$this->assertEquals(123, $exCall->parameter()->value()->literalValue());
		$this->assertEquals(123, $exCall->target()->value()->literalValue());

		$exMethod = $method(
			$constant($list([])),
			"Length",
			$constant($null()));
		$this->assertEquals("Length", $exMethod->methodName()->identifier);
		$this->assertNull($exMethod->parameter()->value()->literalValue());
		$this->assertEquals($list([]), $exMethod->target()->value());

		$exConstructorCall = $constructor("Foo", $exConstant);
		$this->assertEquals(123, $exConstructorCall->parameter()->value()->literalValue());
		$this->assertEquals("Foo", $exConstructorCall->typeName()->identifier);

		$exPropertyAccess = $property($exConstant, "foo");
		$this->assertEquals(123, $exPropertyAccess->target()->value()->literalValue());
		$this->assertEquals("foo", $exPropertyAccess->propertyName());
	}

	public function testMethodRegistry(): void {
		$functions = $this->builder->methodBuilder();
		extract($functions);

		$method = $addMethod(
			$this->typeRegistry->real(),
			"Length",
			$this->typeRegistry->any(),
			$this->typeRegistry->string(),
			$this->typeRegistry->integer(),
			$fb = $this->expressionRegistry->functionBody(
				$this->expressionRegistry->sequence([
				$this->expressionRegistry->constant($this->valueRegistry->integer(123)),
			])
		));
		$this->assertEquals("Length", $method->methodName()->identifier);
		$this->assertEquals($this->typeRegistry->real(), $method->targetType());
		$this->assertEquals($this->typeRegistry->any(), $method->parameterType());
		$this->assertEquals($this->typeRegistry->string(), $method->dependencyType());
		$this->assertEquals($this->typeRegistry->integer(), $method->returnType());
		$this->assertEquals($fb, $method->functionBody());
	}

	public function testInvalidMainFunction(): void {
		$this->expectException(LogicException::class);
		$this->builder->callFunction(
			new VariableNameIdentifier('main'),
			$this->typeRegistry->any(),
			$this->typeRegistry->any(),
			$this->valueRegistry->null()
		);
	}

}