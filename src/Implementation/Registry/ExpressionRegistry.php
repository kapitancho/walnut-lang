<?php

namespace Walnut\Lang\Implementation\Registry;

use Walnut\Lang\Blueprint\Expression\Expression;
use Walnut\Lang\Blueprint\Expression\MatchExpressionDefault;
use Walnut\Lang\Blueprint\Expression\MatchExpressionOperation;
use Walnut\Lang\Blueprint\Expression\MatchExpressionPair;
use Walnut\Lang\Blueprint\Identifier\MethodNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\PropertyNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\ExpressionRegistry as ExpressionRegistryInterface;
use Walnut\Lang\Blueprint\Registry\MethodRegistry;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Expression\ConstantExpression;
use Walnut\Lang\Implementation\Expression\ConstructorCallExpression;
use Walnut\Lang\Implementation\Expression\FunctionCallExpression;
use Walnut\Lang\Implementation\Expression\MatchExpression;
use Walnut\Lang\Implementation\Expression\MethodCallExpression;
use Walnut\Lang\Implementation\Expression\NoErrorExpression;
use Walnut\Lang\Implementation\Expression\PropertyAccessExpression;
use Walnut\Lang\Implementation\Expression\RecordExpression;
use Walnut\Lang\Implementation\Expression\ReturnExpression;
use Walnut\Lang\Implementation\Expression\SequenceExpression;
use Walnut\Lang\Implementation\Expression\TupleExpression;
use Walnut\Lang\Implementation\Expression\VariableAssignmentExpression;
use Walnut\Lang\Implementation\Expression\VariableNameExpression;
use Walnut\Lang\Implementation\Function\FunctionBody;

final readonly class ExpressionRegistry implements ExpressionRegistryInterface {
	public function __construct(
		private TypeRegistry             $typeRegistry,
		private ValueRegistry            $valueRegistry,
		private MethodRegistry           $methodRegistry,
	) {}

	public function constant(Value $value): ConstantExpression {
		return new ConstantExpression($this->typeRegistry, $value);
	}

	/** @param list<MatchExpressionPair> $values */
	public function tuple(array $values): TupleExpression {
		return new TupleExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$values
		);
	}

	/** @param array<string, Expression> $values */
	public function record(array $values): RecordExpression {
		return new RecordExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$values
		);
	}

	/** @param list<MatchExpressionPair> $values */
	public function sequence(array $values): SequenceExpression {
		return new SequenceExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$values
		);
	}

	public function return(Expression $returnedExpression): ReturnExpression {
		return new ReturnExpression(
			$this->typeRegistry,
			$returnedExpression
		);
	}
	public function noError(Expression $targetExpression): NoErrorExpression {
		return new NoErrorExpression(
			$this->typeRegistry,
			$targetExpression
		);
	}
	public function variableName(VariableNameIdentifier $variableName): VariableNameExpression {
		return new VariableNameExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$variableName
		);
	}
	public function variableAssignment(
		VariableNameIdentifier $variableName,
		Expression $assignedExpression
	): VariableAssignmentExpression {
		return new VariableAssignmentExpression(
			$this->typeRegistry,
			$variableName,
			$assignedExpression
		);
	}

	/** @param list<MatchExpressionPair|MatchExpressionDefault> $pairs */
	public function match(
		Expression $target,
		MatchExpressionOperation $operation,
		array $pairs
	): MatchExpression {
		return new MatchExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$target,
			$operation,
			$pairs
		);
	}
	public function functionCall(
		Expression $target,
		Expression $parameter
	): FunctionCallExpression {
		return new FunctionCallExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$this->methodRegistry,
			$target,
			$parameter
		);
	}
	public function methodCall(
		Expression $target,
		MethodNameIdentifier $methodName,
		Expression $parameter
	): MethodCallExpression {
		return new MethodCallExpression(
			$this->typeRegistry,
			$this->methodRegistry,
			$target,
			$methodName,
			$parameter,
		);
	}

	public function constructorCall(
		TypeNameIdentifier $typeName,
		Expression $parameter
	): ConstructorCallExpression {
		return new ConstructorCallExpression(
			$this->typeRegistry,
			$this->valueRegistry,
			$this->methodRegistry,
			$typeName,
			$parameter
		);
	}

	public function propertyAccess(
		Expression $target,
		PropertyNameIdentifier $propertyName
	): PropertyAccessExpression {
		return new PropertyAccessExpression(
			$this->typeRegistry,
			$target,
			$propertyName
		);
	}

	public function functionBody(Expression $expression): FunctionBody {
		return new FunctionBody($expression);
	}
}