<?php

namespace Walnut\Lang\Implementation\Registry;

use JsonSerializable;
use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\TypedValue;
use Walnut\Lang\Blueprint\Execution\VariableValuePair;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Registry\TypeRegistry;
use Walnut\Lang\Blueprint\Registry\ValueRegistry as ValueRegistryInterface;
use Walnut\Lang\Blueprint\Registry\ValueRegistryBuilder;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Blueprint\Value\AtomValue as AtomValueInterface;
use Walnut\Lang\Blueprint\Value\EnumerationValue as EnumerationValueInterface;
use Walnut\Lang\Blueprint\Value\FunctionBodyException;
use Walnut\Lang\Blueprint\Value\UnknownEnumerationValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Execution\VariableValueScope;
use Walnut\Lang\Implementation\Value\BooleanValue;
use Walnut\Lang\Implementation\Value\DictValue;
use Walnut\Lang\Implementation\Value\ErrorValue;
use Walnut\Lang\Implementation\Value\FunctionValue;
use Walnut\Lang\Implementation\Value\IntegerValue;
use Walnut\Lang\Implementation\Value\ListValue;
use Walnut\Lang\Implementation\Value\MutableValue;
use Walnut\Lang\Implementation\Value\NullValue;
use Walnut\Lang\Implementation\Value\RealValue;
use Walnut\Lang\Implementation\Value\StateValue;
use Walnut\Lang\Implementation\Value\StringValue;
use Walnut\Lang\Implementation\Value\SubtypeValue;
use Walnut\Lang\Implementation\Value\TypeValue;

final class ValueRegistry implements ValueRegistryInterface, ValueRegistryBuilder, JsonSerializable {
	/** @var array<string, VariableValuePair> */
	private array $pairs;
	public function __construct(private readonly TypeRegistry $typeRegistry) {
		$this->pairs = [];
	}

	public function null(): NullValue {
		return $this->typeRegistry->null()->value();
	}

    public function boolean(bool $value): BooleanValue {
		return $value ? $this->true() : $this->false();
	}

    public function true(): BooleanValue {
	    return $this->typeRegistry->true()->value();
	}

    public function false(): BooleanValue {
	    return $this->typeRegistry->false()->value();
	}

    public function integer(int $value): IntegerValue {
		return new IntegerValue(
			$this->typeRegistry,
			$value
		);
	}

    public function real(float $value): RealValue {
	    return new RealValue($this->typeRegistry, $value);
	}

    public function string(string $value): StringValue {
	    return new StringValue($this->typeRegistry, $value);
	}

	/** @param list<Value> $values */
	public function list(array $values): ListValue {
		return new ListValue(
			$this->typeRegistry,
			$values
		);
	}

	/** @param array<string, Value> $values */
	public function dict(array $values): DictValue {
		return new DictValue(
			$this->typeRegistry,
			$values
		);
	}

	public function mutable(Type $type, Value $value): MutableValue {
		return new MutableValue(
			$this->typeRegistry,
			$type,
			$value
		);
	}

	public function function(
		Type $parameterType, Type $returnType, FunctionBody $body
    ): FunctionValue {
		return new FunctionValue(
			$this->typeRegistry,
			$parameterType,
			$returnType,
			$body,
			null,
			null
		);
    }

    public function type(Type $type): TypeValue {
		return new TypeValue($this->typeRegistry, $type);
	}

    public function error(Value $value): ErrorValue {
        return new ErrorValue($this->typeRegistry, $value);
    }

    /** @throws UnknownType */
    public function atom(TypeNameIdentifier $typeName): AtomValueInterface {
		return $this->typeRegistry->atom($typeName)->value();
	}

    /** @throws UnknownType */
    /** @throws UnknownEnumerationValue */
    public function enumerationValue(
        TypeNameIdentifier $typeName,
        EnumValueIdentifier $valueIdentifier
    ): EnumerationValueInterface {
		return $this->typeRegistry->enumeration($typeName)
			->value($valueIdentifier);
	}

	/** @throws UnknownType */
    public function subtypeValue(
        TypeNameIdentifier $typeName,
        Value $baseValue
    ): SubtypeValue {
		return new SubtypeValue(
			$this->typeRegistry,
			$typeName,
			$baseValue
		);
	}

	/** @throws UnknownType */
    public function stateValue(
        TypeNameIdentifier $typeName,
        Value $stateValue
    ): StateValue {
		return new StateValue(
			$this->typeRegistry,
			$typeName,
			$stateValue
		);
	}

	public function variables(): VariableValueScope {
		return VariableValueScope::fromPairs(... $this->pairs);
	}

	public function addVariable(
		VariableNameIdentifier $name, Value $value
	): VariableValuePair {
		return $this->pairs[$name->identifier] = new VariableValuePair($name,
			new TypedValue($value->type(), $value)
		);
	}

	public function analyse(): void {
		foreach($this->pairs as $pair) {
			$val = $pair->typedValue->value;
			if ($val instanceof FunctionValue) {
				try {
					$val->analyse();
				} catch(FunctionBodyException $e) {
					throw new AnalyserException(
						sprintf("Error in variable '%s': %s",
							$pair->variableName, $e->getMessage())
					);
				}
			}
		}
	}

	public function build(): ValueRegistryInterface {
		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'globalValues' => $this->pairs
		];
	}
}