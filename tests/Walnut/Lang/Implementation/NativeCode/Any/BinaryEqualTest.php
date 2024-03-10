<?php

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class BinaryEqualTest extends BaseProgramTestHelper {

	private function callBinaryEqual(Value $value1, Value $value2, bool $expected): void {
		$this->testMethodCall(
			$this->expressionRegistry->constant($value1),
			'binaryEqual',
			$this->expressionRegistry->constant($value2),
			$this->valueRegistry->boolean($expected)
		);
		$this->testMethodCall(
			$this->expressionRegistry->constant($value2),
			'binaryEqual',
			$this->expressionRegistry->constant($value1),
			$this->valueRegistry->boolean($expected)
		);
	}

	public function testBinaryEqual(): void {
		$c1 = $this->valueRegistry->integer(123);
		$c2 = $this->valueRegistry->integer(456);
		$cx = $this->valueRegistry->real(123);
		$z = $c = $this->valueRegistry->integer(123);

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);

		$this->callBinaryEqual($cx, $c1, true);
		$this->callBinaryEqual($cx, $c2, false);

		$c1 = $this->valueRegistry->real(3.14);
		$c2 = $this->valueRegistry->real(4.57);
		$c = $this->valueRegistry->real(3.14);

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->string("Hi");
		$c2 = $this->valueRegistry->string("Hello");
		$c = $this->valueRegistry->string("Hi");

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->boolean(true);
		$c2 = $this->valueRegistry->boolean(false);
		$c = $this->valueRegistry->boolean(true);

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->list([$this->valueRegistry->true(), $this->valueRegistry->null()]);
		$c2 = $this->valueRegistry->list([$this->valueRegistry->true(), $this->valueRegistry->false()]);
		$c = $this->valueRegistry->list([$this->valueRegistry->true(), $this->valueRegistry->null()]);

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->dict(['a' => $this->valueRegistry->true(), 'b' => $this->valueRegistry->null()]);
		$c2 = $this->valueRegistry->dict(['a' => $this->valueRegistry->true(), 'b' => $this->valueRegistry->false()]);
		$c3 = $this->valueRegistry->dict(['a' => $this->valueRegistry->true(), 'c' => $this->valueRegistry->false()]);
		$c = $this->valueRegistry->dict(['a' => $this->valueRegistry->true(), 'b' => $this->valueRegistry->null()]);

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $z, false);


		$c1 = $this->valueRegistry->mutable($this->typeRegistry->boolean(), $this->valueRegistry->boolean(true));
		$c2 = $this->valueRegistry->mutable($this->typeRegistry->boolean(), $this->valueRegistry->boolean(false));
		$c3 = $this->valueRegistry->mutable($this->typeRegistry->any(), $this->valueRegistry->boolean(true));
		$c4 = $this->valueRegistry->boolean(true);
		$c = $this->valueRegistry->mutable($this->typeRegistry->boolean(), $this->valueRegistry->boolean(true));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $c4, false);
		$this->callBinaryEqual($c, $z, false);

		$this->builder->typeBuilder()['addEnumeration']('E', ['A', 'B', 'C']);
		$this->builder->typeBuilder()['addEnumeration']('G', ['A', 'X', 'Y']);

		$c1 = $this->valueRegistry->enumerationValue(new TypeNameIdentifier('E'), new EnumValueIdentifier('A'));
		$c2 = $this->valueRegistry->enumerationValue(new TypeNameIdentifier('E'), new EnumValueIdentifier('B'));
		$c3 = $this->valueRegistry->enumerationValue(new TypeNameIdentifier('G'), new EnumValueIdentifier('A'));
		$c = $this->valueRegistry->enumerationValue(new TypeNameIdentifier('E'), new EnumValueIdentifier('A'));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $z, false);

		$this->builder->typeBuilder()['addAtom']('H');
		$this->builder->typeBuilder()['addAtom']('J');

		$c1 = $this->valueRegistry->atom(new TypeNameIdentifier('H'));
		$c2 = $this->valueRegistry->atom(new TypeNameIdentifier('J'));
		$c = $this->valueRegistry->atom(new TypeNameIdentifier('H'));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->type($this->typeRegistry->boolean());
		$c2 = $this->valueRegistry->type($this->typeRegistry->any());
		$c = $this->valueRegistry->type($this->typeRegistry->boolean());

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);

		$this->builder->typeBuilder()['addSubtype']('K', $this->typeRegistry->boolean(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->boolean(true))
			), null
		);
		$this->builder->typeBuilder()['addSubtype']('L', $this->typeRegistry->boolean(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->boolean(true))
			), null
		);

		$c1 = $this->valueRegistry->subtypeValue(new TypeNameIdentifier('K'), $this->valueRegistry->boolean(true));
		$c2 = $this->valueRegistry->subtypeValue(new TypeNameIdentifier('K'), $this->valueRegistry->boolean(false));
		$c3 = $this->valueRegistry->subtypeValue(new TypeNameIdentifier('L'), $this->valueRegistry->boolean(true));
		$c = $this->valueRegistry->subtypeValue(new TypeNameIdentifier('K'), $this->valueRegistry->boolean(true));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $z, false);


		$this->builder->typeBuilder()['addState']('M', $this->typeRegistry->boolean());
		$this->builder->typeBuilder()['addState']('N', $this->typeRegistry->boolean());

		$c1 = $this->valueRegistry->stateValue(new TypeNameIdentifier('M'), $this->valueRegistry->boolean(true));
		$c2 = $this->valueRegistry->stateValue(new TypeNameIdentifier('M'), $this->valueRegistry->boolean(false));
		$c3 = $this->valueRegistry->stateValue(new TypeNameIdentifier('N'), $this->valueRegistry->boolean(true));
		$c = $this->valueRegistry->stateValue(new TypeNameIdentifier('M'), $this->valueRegistry->boolean(true));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $z, false);

		$c1 = $this->valueRegistry->function(
			$this->typeRegistry->boolean(),
			$this->typeRegistry->boolean(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->boolean(true))
			)
		);
		$c2 = $this->valueRegistry->function(
			$this->typeRegistry->boolean(),
			$this->typeRegistry->boolean(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant($this->valueRegistry->boolean(false))
			)
		);
		$c = $c1;

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $z, false);


		$c1 = $this->valueRegistry->error($this->valueRegistry->boolean(true));
		$c2 = $this->valueRegistry->error($this->valueRegistry->boolean(false));
		$c3 = $this->valueRegistry->boolean(true);
		$c = $this->valueRegistry->error($this->valueRegistry->boolean(true));

		$this->callBinaryEqual($c, $c1, true);
		$this->callBinaryEqual($c, $c2, false);
		$this->callBinaryEqual($c, $c3, false);
		$this->callBinaryEqual($c, $z, false);
	}
}
