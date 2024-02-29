<?php

namespace Walnut\Lang\Implementation\Value;

use Walnut\Lang\Blueprint\Execution\ReturnResult;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Execution\VariableScope as VariableScopeInterface;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Execution\VariableValueScope as VariableValueScopeInterface;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\FunctionBodyException;
use Walnut\Lang\Blueprint\Value\FunctionValue as FunctionValueInterface;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Registry\TypeRegistry;
use Walnut\Lang\Implementation\Type\FunctionType;

final readonly class FunctionValue implements FunctionValueInterface {

    public function __construct(
		private TypeRegistry $typeRegistry,
		private Type $parameterType,
		private Type $returnType,
	    private FunctionBody $body,
	    private VariableValueScopeInterface|null $variableValueScope,
	    private VariableNameIdentifier|null $selfReferAs
    ) {}

	public function withVariableValueScope(VariableValueScopeInterface $variableValueScope): self {
		return new self(
			$this->typeRegistry,
			$this->parameterType,
			$this->returnType,
			$this->body,
			$variableValueScope,
			$this->selfReferAs
		);
	}

	public function withSelfReferenceAs(VariableNameIdentifier $variableName): self {
		return new self(
			$this->typeRegistry,
			$this->parameterType,
			$this->returnType,
			$this->body,
			$this->variableValueScope,
			$variableName
		);
	}

    public function type(): FunctionType {
        return $this->typeRegistry->function(
			$this->parameterType,
			$this->returnType
        );
    }

	public function parameterType(): Type {
		return $this->parameterType;
	}

	public function returnType(): Type {
		return $this->returnType;
	}

    public function body(): FunctionBody {
		return $this->body;
    }

	/** @throws FunctionBodyException */
	public function analyse(VariableScopeInterface $variableScope = null): void {
		$variableScope ??= VariableScope::empty();
		$variableScope = $variableScope->withAddedVariablePairs(
			new VariablePair(
				new VariableNameIdentifier('#'),
				$this->parameterType,
			),
		);
		if ($this->selfReferAs) {
			$variableScope = $variableScope->withAddedVariablePairs(
				new VariablePair(
					$this->selfReferAs,
					$this->typeRegistry->function(
						$this->parameterType,
						$this->returnType
					),
				),
			);
		}
		$result = $this->body->expression()->analyse($variableScope);
		$returnType = $this->typeRegistry->union([
			$result->returnType,
			$result->expressionType
		]);
		if (!$returnType->isSubtypeOf($this->returnType)) {
			throw new FunctionBodyException(
				sprintf(
					"Function return type \n %s is not a subtype of \n %s",
					$returnType,
					$this->returnType
				)
			);
		}
	}

	public function execute(Value $value): Value {
		$variableValueScope = $this->variableValueScope ?? VariableValueScope::empty();
		$variableValueScope = $variableValueScope->withAddedValues(
			new VariableValuePair(
				new VariableNameIdentifier('#'),
				new TypedValue(
					$this->parameterType,
					$value
				)
			),
		);
		if ($this->selfReferAs) {
			$variableValueScope = $variableValueScope->withAddedValues(
				new VariableValuePair(
					$this->selfReferAs,
					new TypedValue(
						$this->typeRegistry->function(
							$this->parameterType,
							$this->returnType
						),
						$this
					)
				),
			);
		}
		try {
			return $this->body->expression()->execute($variableValueScope)->value();
		} catch (ReturnResult $result) {
			return $result->value;
		}
	}

	public function equals(Value $other): bool {
		return $other instanceof self && (string)$this === (string)$other;
	}

	public function __toString(): string {
		return sprintf(
			"^%s => %s :: %s",
			$this->parameterType,
			$this->returnType,
			$this->body
		);
	}
}