<?php

namespace Walnut\Lang\Implementation\Registry;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\VariablePair;
use Walnut\Lang\Blueprint\Function\FunctionBody;
use Walnut\Lang\Blueprint\Identifier\EnumValueIdentifier;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\Identifier\VariableNameIdentifier;
use Walnut\Lang\Blueprint\Range\MinusInfinity;
use Walnut\Lang\Blueprint\Range\PlusInfinity;
use Walnut\Lang\Blueprint\Registry\TypeRegistry as TypeRegistryInterface;
use Walnut\Lang\Blueprint\Registry\TypeRegistryBuilder;
use Walnut\Lang\Blueprint\Type\AliasType as AliasTypeInterface;
use Walnut\Lang\Blueprint\Type\AtomType as AtomTypeInterface;
use Walnut\Lang\Blueprint\Type\EnumerationType as EnumerationTypeInterface;
use Walnut\Lang\Blueprint\Type\NamedType as NamedTypeInterface;
use Walnut\Lang\Blueprint\Type\ResultType as ResultTypeInterface;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Type\UnknownType;
use Walnut\Lang\Blueprint\Value\IntegerValue;
use Walnut\Lang\Blueprint\Value\RealValue;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Implementation\Execution\VariableScope;
use Walnut\Lang\Implementation\Range\IntegerRange;
use Walnut\Lang\Implementation\Range\LengthRange;
use Walnut\Lang\Implementation\Range\RealRange;
use Walnut\Lang\Implementation\Type\AliasType;
use Walnut\Lang\Implementation\Type\AnyType;
use Walnut\Lang\Implementation\Type\ArrayType;
use Walnut\Lang\Implementation\Type\AtomType;
use Walnut\Lang\Implementation\Type\BooleanType;
use Walnut\Lang\Implementation\Type\EnumerationType;
use Walnut\Lang\Implementation\Type\FalseType;
use Walnut\Lang\Implementation\Type\FunctionType;
use Walnut\Lang\Implementation\Type\IntegerSubsetType;
use Walnut\Lang\Implementation\Type\IntegerType;
use Walnut\Lang\Implementation\Type\IntersectionType;
use Walnut\Lang\Implementation\Type\ProxyNamedType;
use Walnut\Lang\Implementation\Type\MapType;
use Walnut\Lang\Implementation\Type\MutableType;
use Walnut\Lang\Implementation\Type\NothingType;
use Walnut\Lang\Implementation\Type\NullType;
use Walnut\Lang\Implementation\Type\RealSubsetType;
use Walnut\Lang\Implementation\Type\RealType;
use Walnut\Lang\Implementation\Type\RecordType;
use Walnut\Lang\Implementation\Type\ResultType;
use Walnut\Lang\Implementation\Type\StateType;
use Walnut\Lang\Implementation\Type\StringSubsetType;
use Walnut\Lang\Implementation\Type\StringType;
use Walnut\Lang\Implementation\Type\SubtypeType;
use Walnut\Lang\Implementation\Type\TrueType;
use Walnut\Lang\Implementation\Type\TupleType;
use Walnut\Lang\Implementation\Type\TypeType;
use Walnut\Lang\Implementation\Type\UnionType;
use Walnut\Lang\Implementation\Value\AtomValue;
use Walnut\Lang\Implementation\Value\BooleanValue;
use Walnut\Lang\Implementation\Value\EnumerationValue;
use Walnut\Lang\Implementation\Value\NullValue;

final class TypeRegistry implements TypeRegistryInterface, TypeRegistryBuilder {

    private AnyType $anyType;
    private NothingType $nothingType;

    private BooleanType $booleanType;
    private TrueType $trueType;
    private FalseType $falseType;

    private NullType $nullType;

	/** @var array<string, AtomType> */
    private array $atomTypes;
	/** @var array<string, EnumerationType> */
    private array $enumerationTypes;
	/** @var array<string, AliasType> */
    private array $aliasTypes;
	/** @var array<string, SubtypeType> */
    private array $subtypeTypes;
	/** @var array<string, StateType> */
    private array $stateTypes;

    private UnionTypeNormalizer $unionTypeNormalizer;
    private IntersectionTypeNormalizer $intersectionTypeNormalizer;

    private const booleanTypeName = 'Boolean';
    private const nullTypeName = 'Null';
    private const otherTypes = [];//['NotANumber', 'MinusInfinity', 'PlusInfinity', 'DependencyContainer', 'Constructor'];

    public function __construct() {
        $this->unionTypeNormalizer = new UnionTypeNormalizer($this);
        $this->intersectionTypeNormalizer = new IntersectionTypeNormalizer($this);

        $this->anyType = new AnyType;
        $this->nothingType = new NothingType;
        $atomTypes = [
			self::nullTypeName => $this->nullType = new NullType(
				new TypeNameIdentifier(self::nullTypeName),
	            new NullValue($this)
	        )
        ];
        foreach(self::otherTypes as $typeName) {
            $atomTypes[$typeName] = new AtomType(
                $n = new TypeNameIdentifier($typeName),
                new AtomValue($this, $n)
            );
        }

        $this->atomTypes = $atomTypes;
        $enumerationTypes = [
			self::booleanTypeName => $this->booleanType = new BooleanType(
	            new TypeNameIdentifier(self::booleanTypeName),
	            new BooleanValue(
					$this,
					$trueValue = new EnumValueIdentifier('True'),
	                true
	            ),
	            new BooleanValue(
					$this,
					$falseValue = new EnumValueIdentifier('False'),
	                false
	            )
	        )
        ];
        $this->trueType = $this->booleanType->subsetType([$trueValue]);
        $this->falseType = $this->booleanType->subsetType([$falseValue]);
        $this->enumerationTypes = $enumerationTypes;
		$this->aliasTypes = [];
		$this->subtypeTypes = [];
		$this->stateTypes = [];
    }

    private function namedType(TypeNameIdentifier $typeName): ProxyNamedType {
        return new ProxyNamedType($typeName, $this);
    }

    public function any(): AnyType {
        return $this->anyType;
    }

    public function nothing(): NothingType {
        return $this->nothingType;
    }

    public function null(): NullType {
        return $this->nullType;
    }

    public function boolean(): BooleanType {
        return $this->booleanType;
    }
    public function true(): TrueType {
        return $this->trueType;
    }
    public function false(): FalseType {
        return $this->falseType;
    }

    public function integer(
	    int|MinusInfinity $min = MinusInfinity::value,
        int|PlusInfinity $max = PlusInfinity::value
    ): IntegerType {
        return new IntegerType(
			$this,
			new IntegerRange($min, $max)
        );
    }
	/** @param list<IntegerValue> $values */
	public function integerSubset(array $values): IntegerSubsetType {
		return new IntegerSubsetType($values);
	}

    public function real(
	    float|MinusInfinity $min = MinusInfinity::value,
	    float|PlusInfinity $max = PlusInfinity::value
    ): RealType {
        return new RealType(new RealRange($min, $max));
    }
	/** @param list<RealValue> $values */
	public function realSubset(array $values): RealSubsetType {
		return new RealSubsetType($values);
	}

    public function string(
	    int $minLength = 0,
	    int|PlusInfinity $maxLength = PlusInfinity::value
    ): StringType {
        return new StringType(new LengthRange($minLength, $maxLength));
    }
	/** @param list<StringValue> $values */
	public function stringSubset(array $values): StringSubsetType {
		return new StringSubsetType($values);
	}

    public function result(Type $returnType, Type $errorType): ResultType {
		if ($returnType instanceof ResultTypeInterface) {
			$errorType = $this->union([$errorType, $returnType->errorType()]);
			$returnType = $returnType->returnType();
		}
        return new ResultType($returnType, $errorType);
    }

    public function type(Type $refType): TypeType {
        return new TypeType($refType);
    }

	public function proxyType(TypeNameIdentifier $typeName): ProxyNamedType {
		return new ProxyNamedType($typeName, $this);
	}

    public function withName(TypeNameIdentifier $typeName): NamedTypeInterface {
        return
	        $this->atomTypes[$typeName->identifier] ??
            $this->enumerationTypes[$typeName->identifier] ??
            $this->aliasTypes[$typeName->identifier] ??
            $this->subtypeTypes[$typeName->identifier] ??
            $this->stateTypes[$typeName->identifier] ??
            UnknownType::withName($typeName);
    }

	/** @throws UnknownType */
	public function alias(TypeNameIdentifier $typeName): AliasType {
		return $this->aliasTypes[$typeName->identifier] ?? UnknownType::withName($typeName);
	}

	/** @throws UnknownType */
	public function subtype(TypeNameIdentifier $typeName): SubtypeType {
		return $this->subtypeTypes[$typeName->identifier] ?? UnknownType::withName($typeName);
	}

	/** @throws UnknownType */
	public function state(TypeNameIdentifier $typeName): StateType {
		return $this->stateTypes[$typeName->identifier] ?? UnknownType::withName($typeName);
	}

    public function atom(TypeNameIdentifier $typeName): AtomTypeInterface {
        return $this->atomTypes[$typeName->identifier] ?? UnknownType::withName($typeName);
    }

    public function enumeration(TypeNameIdentifier $typeName): EnumerationTypeInterface {
        return $this->enumerationTypes[$typeName->identifier] ?? UnknownType::withName($typeName);
    }

	//public function enumerationSubset(array $values): EnumerationSubsetType {}

	public function array(Type $itemType = null, int $minLength = 0, int|PlusInfinity $maxLength = PlusInfinity::value): ArrayType {
		return new ArrayType($itemType ?? $this->anyType, new LengthRange($minLength, $maxLength));
	}

	public function map(Type $itemType = null, int $minLength = 0, int|PlusInfinity $maxLength = PlusInfinity::value): MapType {
		return new MapType($itemType ?? $this->anyType, new LengthRange($minLength, $maxLength));
	}

	/** @param list<Type> $itemTypes */
	public function tuple(array $itemTypes, Type $restType = null): TupleType {
		return new TupleType($this, $itemTypes, $restType ?? $this->nothingType);
	}

	/** @param array<string, Type> $itemTypes */
	public function record(array $itemTypes, Type $restType = null): RecordType {
		return new RecordType($this, $itemTypes, $restType ?? $this->nothingType);
	}

	/** @param list<Type> $types */
	public function union(array $types, bool $normalize = true): Type {
		if (count($types) === 1 && $types[0] instanceof AliasTypeInterface) {
			return $types[0];
		}
		return $normalize ? $this->unionTypeNormalizer->normalize(... $types) :
			new UnionType(...$types);
	}

	/** @param list<Type> $types */
	public function intersection(array $types, bool $normalize = true): Type {
        return $normalize ? $this->intersectionTypeNormalizer->normalize(... $types) :
            new IntersectionType(...$types);
	}

	public function function(Type $parameterType, Type $returnType): FunctionType {
		return new FunctionType($parameterType, $returnType);
	}

	public function mutable(Type $valueType): MutableType {
		return new MutableType($valueType);
	}

	public static function emptyRegistry(): self {
		return new self;
	}

	public function addAtom(TypeNameIdentifier $name): AtomType {
		$result = new AtomType(
			$name,
			new AtomValue($this, $name)
		);
		$this->atomTypes[$name->identifier] = $result;
		return $result;
	}

	/** @param list<EnumValueIdentifier> $values */
	public function addEnumeration(TypeNameIdentifier $name, array $values): EnumerationType {
		$result = new EnumerationType(
			$name,
			array_combine(
				array_map(
					static fn(EnumValueIdentifier $value): string => $value->identifier,
					$values
				),
				array_map(
					fn(EnumValueIdentifier $value): EnumerationValue =>
						new EnumerationValue($this, $name, $value),
					$values
				)
			)
		);
		$this->enumerationTypes[$name->identifier] = $result;
		return $result;
	}

	public function addAlias(TypeNameIdentifier $name, Type $aliasedType): AliasType {
		$result = new AliasType($name, $aliasedType);
		$this->aliasTypes[$name->identifier] = $result;
		return $result;
	}

	public function addSubtype(
		TypeNameIdentifier $name,
		Type $baseType,
		FunctionBody $constructorBody,
		Type|null $errorType
	): SubtypeType {
		$result = new SubtypeType($name, $baseType, $constructorBody, $errorType);
		$this->subtypeTypes[$name->identifier] = $result;
		return $result;
	}

	public function addState(
		TypeNameIdentifier $name,
		Type $stateType
	): StateType {
		$result = new StateType($name, $stateType);
		$this->stateTypes[$name->identifier] = $result;
		return $result;
	}

	public function build(): TypeRegistry {
		return $this;
	}

	public function analyse(): void {
		foreach($this->subtypeTypes as $subtypeType) {
			$e = $subtypeType->errorType() ?? $this->nothingType;
			$retBody = $subtypeType->constructorBody()->expression()->analyse(
				VariableScope::fromPairs(
					new VariablePair(
						new VariableNameIdentifier('#'),
						$subtypeType->baseType(),
					)
				)
			);
			$retType = $this->union([
				$retBody->returnType,
				$retBody->expressionType,
			]);
			if ($retType instanceof ResultTypeInterface) {
				if (!$retType->errorType()->isSubtypeOf($e)) {
					throw new AnalyserException(sprintf(
						"Subtype %s error type is %s but the constructor returns %s",
						$subtypeType->name(),
						$e,
						$retType->errorType()
					));
				}
			}
		}
	}

}