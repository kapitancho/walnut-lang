<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Value\FunctionBodyException;
use Walnut\Lang\Test\Implementation\BaseProgramTestHelper;

final class FunctionValueTest extends BaseProgramTestHelper {

	public function testReturnTypeOk(): void {
		$this->expectNotToPerformAssertions();
		$fn = $this->valueRegistry->function(
			$this->typeRegistry->integer(10, 20),
			$this->typeRegistry->integer(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
			)
		);
		$fn->analyse();
	}

	public function testReturnTypeNotOk(): void {
		$this->expectException(FunctionBodyException::class);
		$fn = $this->valueRegistry->function(
			$this->typeRegistry->integer(),
			$this->typeRegistry->integer(10, 20),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
			)
		);
		$fn->analyse();
	}

	public function testReturnValueOk(): void {
		$fn = $this->valueRegistry->function(
			$this->typeRegistry->integer(10, 20),
			$this->typeRegistry->integer(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
			)
		);
		$result = $fn->execute($int = $this->valueRegistry->integer(15));
		$this->assertTrue($result->equals($int));
	}

	public function testReturnValueDirectReturnOk(): void {
		$fn = $this->valueRegistry->function(
			$this->typeRegistry->integer(10, 20),
			$this->typeRegistry->integer(),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->return(
					$this->expressionRegistry->variableName(new VariableNameIdentifier('#'))
				)
			)
		);
		$result = $fn->execute($int = $this->valueRegistry->integer(15));
		$this->assertTrue($result->equals($int));
	}
}