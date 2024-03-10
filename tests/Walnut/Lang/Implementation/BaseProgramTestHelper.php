<?php

namespace Walnut\Lang\Test\Implementation;

use PHPUnit\Framework\TestCase;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Registry\CustomMethodRegistryBuilder;
use Walnut\Lang\Implementation\Registry\ExpressionRegistry;
use Walnut\Lang\Implementation\Registry\ProgramBuilder;
use Walnut\Lang\Implementation\Registry\ProgramBuilderFactory;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Registry\ValueRegistry;
use Walnut\Lang\Implementation\Type\UnionType;

abstract class BaseProgramTestHelper extends TestCase {
	protected TypeRegistry $typeRegistry;
	protected ValueRegistry $valueRegistry;
	protected ExpressionRegistry $expressionRegistry;
	protected CustomMethodRegistryBuilder $customMethodRegistryBuilder;
	protected ProgramBuilder $builder;

	protected function setUp(): void {
		parent::setUp();

		$f = new ProgramBuilderFactory;
		$this->builder = $f->getProgramBuilder();
		$this->typeRegistry = $f->typeRegistry;
		$this->valueRegistry = $f->valueRegistry;
		$this->expressionRegistry = $f->expressionRegistry;
		$this->customMethodRegistryBuilder = $f->customMethodRegistryBuilder;
	}
	
	protected function addCoreToContext(): void {
		foreach(['NotANumber', 'MinusInfinity', 'PlusInfinity', 'DependencyContainer', 'Constructor'] as $atomType) {
			$this->typeRegistry->addAtom(
				new TypeNameIdentifier($atomType)
			);
		}
		$this->typeRegistry->addState(
			new TypeNameIdentifier('IndexOutOfRange'),
			$this->typeRegistry->integer(),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('CastNotAvailable'),
			$this->typeRegistry->record([
				'from' => $this->typeRegistry->type($this->typeRegistry->any()),
				'to' => $this->typeRegistry->type($this->typeRegistry->any()),
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('MapItemNotFound'),
			$this->typeRegistry->string(),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('InvalidJsonValue'),
			$this->typeRegistry->record([
				'value' => $this->typeRegistry->any()
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('InvalidJsonString'),
			$this->typeRegistry->record([
				'value' => $this->typeRegistry->string()
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('UnknownEnumerationValue'),
			$this->typeRegistry->record([
				'enumeration' => $this->typeRegistry->type($this->typeRegistry->any()),
				'value' => $this->typeRegistry->string(),
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('DependencyContainerError'),
			$this->typeRegistry->record([
				'targetType' => $this->typeRegistry->type($this->typeRegistry->any()),
				'errorMessage' => $this->typeRegistry->string(),
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('HydrationError'),
			$this->typeRegistry->record([
				'value' => $this->typeRegistry->any(),
				'hydrationPath' => $this->typeRegistry->string(),
				'errorMessage' => $this->typeRegistry->string(),
			]),
		);

		$j = $this->typeRegistry->proxyType(
			new TypeNameIdentifier('JsonValue')
		);

		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('JsonValue'),
			new UnionType(
				$this->typeRegistry->null(),
				$this->typeRegistry->boolean(),
				$this->typeRegistry->integer(),
				$this->typeRegistry->real(),
				$this->typeRegistry->string(),
				$this->typeRegistry->array($j),
				$this->typeRegistry->map($j),
				$this->typeRegistry->mutable($j)
			)
		);

		$this->typeRegistry->addSubtype(
			new TypeNameIdentifier('DatabaseConnection'),
			$this->typeRegistry->record([
				'dsn' => $this->typeRegistry->string()
			]),
			$this->expressionRegistry->functionBody(
				$this->expressionRegistry->constant(
					$this->valueRegistry->null()
				)
			),
			null
		);
		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('DatabaseValue'),
			$this->typeRegistry->union([
				$this->typeRegistry->string(),
				$this->typeRegistry->integer(),
				$this->typeRegistry->boolean(),
				$this->typeRegistry->null()
			])
		);
		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('DatabaseQueryBoundParameters'),
			$this->typeRegistry->union([
				$this->typeRegistry->array(
					$this->typeRegistry->withName(
						new TypeNameIdentifier('DatabaseValue')
					)
				),
				$this->typeRegistry->map(
					$this->typeRegistry->withName(
						new TypeNameIdentifier('DatabaseValue')
					)
				)
			])
		);
		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('DatabaseQueryCommand'),
			$this->typeRegistry->record([
				'query' => $this->typeRegistry->string(1),
				'boundParameters' => $this->typeRegistry->alias(
					new TypeNameIdentifier('DatabaseQueryBoundParameters')
				)
			])
		);
		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('DatabaseQueryResultRow'),
			$this->typeRegistry->map(
				$this->typeRegistry->withName(
					new TypeNameIdentifier('DatabaseValue')
				)
			)
		);
		$this->typeRegistry->addAlias(
			new TypeNameIdentifier('DatabaseQueryResult'),
			$this->typeRegistry->array(
				$this->typeRegistry->withName(
					new TypeNameIdentifier('DatabaseQueryResultRow')
				)
			)
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('DatabaseQueryFailure'),
			$this->typeRegistry->record([
				'query' => $this->typeRegistry->string(1),
				'boundParameters' => $this->typeRegistry->alias(
					new TypeNameIdentifier('DatabaseQueryBoundParameters')
				),
				'error' => $this->typeRegistry->string(),
			]),
		);
		$this->typeRegistry->addState(
			new TypeNameIdentifier('DatabaseConnector'),
			$this->typeRegistry->record([
				'connection' => $this->typeRegistry->subtype(
					new TypeNameIdentifier('DatabaseConnection')
				)
			])
		);		
	}

    protected function testMethodCallAnalyse(
        Type $targetType, string $methodName, Type $parameterType, Type $expectedType
    ): void {
        $call = $this->expressionRegistry->methodCall(
            $this->expressionRegistry->variableName(
                $var = new VariableNameIdentifier('x')
            ),
            new MethodNameIdentifier($methodName),
            $this->expressionRegistry->variableName(
                $var2 = new VariableNameIdentifier('y')
            )
        );
        $result = $call->analyse(VariableScope::fromPairs(
            new VariablePair(
                $var,
                $targetType
            ),
            new VariablePair(
                $var2,
                $parameterType
            )
        ));
        $this->assertTrue(
            $result->expressionType->isSubtypeOf($expectedType)
        );
    }

	protected function testMethodCall(
		Expression $target, string $methodName, Expression $parameter, Value $expectedValue
	): void {
		$call = $this->expressionRegistry->methodCall(
			$target,
			new MethodNameIdentifier($methodName),
			$parameter
		);
		$call->analyse(VariableScope::empty());
		$this->assertTrue(
			($r = $call
				->execute(VariableValueScope::empty())
				->value())->equals($expectedValue),
			sprintf("'%s' is not equal to '%s'", $r, $expectedValue)
		);
	}
}