<?php

namespace Walnut\Lang\Implementation\Type;

use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class AliasTypeTest extends BaseProgramTestHelper {

	public function testProperties(): void {
		$this->builder->typeBuilder()['addAlias']('M', $boolean = $this->typeRegistry->boolean());
		$type = $this->typeRegistry->alias(new TypeNameIdentifier('M'));
		$this->assertEquals($boolean, $type->aliasedType());
		$this->assertEquals($boolean, $type->closestBaseType());
	}

	public function testNestedClosestBaseType(): void {
		$this->builder->typeBuilder()['addAlias']('M', $boolean = $this->typeRegistry->boolean());
		$this->builder->typeBuilder()['addAlias']('N',
			$this->typeRegistry->alias(new TypeNameIdentifier('M'))
		);
		$type = $this->typeRegistry->alias(new TypeNameIdentifier('N'));
		$this->assertEquals($boolean, $type->closestBaseType());
	}

}