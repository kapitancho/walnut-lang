<?php

namespace Walnut\Lang\Test\Implementation\Expression;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\RecordExpression;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;

final class RecordExpressionTest extends TestCase {
	private readonly TypeRegistry $typeRegistry;
	private readonly ValueRegistry $valueRegistry;
	private readonly RecordExpression $recordExpression;

	protected function setUp(): void {
		parent::setUp();
		$this->typeRegistry = TypeRegistry::emptyRegistry();
		$this->valueRegistry = new ValueRegistry($this->typeRegistry);
		$this->recordExpression = new RecordExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			[
				'a' => new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->integer(123)
				),
				'b' => new ConstantExpression(
					$this->typeRegistry,
					$this->valueRegistry->string("456")
				)
			]
		);
	}

	public function testValues(): void {
		self::assertCount(2, $this->recordExpression->values());
	}

	public function testAnalyse(): void {
		$result = $this->recordExpression->analyse(VariableScope::empty());
		self::assertTrue($result->expressionType->isSubtypeOf(
			$this->typeRegistry->record([
				'a' => $this->typeRegistry->integer(),
				'b' => $this->typeRegistry->string()
			])
		));
	}

	public function testExecute(): void {
		$result = $this->recordExpression->execute(VariableValueScope::empty());
		self::assertEquals(
			$this->valueRegistry->dict([
				'a' => $this->valueRegistry->integer(123),
				'b' => $this->valueRegistry->string("456")
			]),
			$result->value()
		);
	}

}