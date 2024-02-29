<?php

namespace Walnut\Lang\Implementation\NativeCode\RoutePattern;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Identifier\TypeNameIdentifier;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\SubtypeType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;
use Walnut\Lang\Implementation\Value\SubtypeValue;

final readonly class MatchAgainst implements Method {

	private const ROUTE_PATTERN_MATCH = '#\{(\+?[\w\_]+)\}#';
	private const ROUTE_PATTERN_REPLACE = ['#\{[\w\_]+\}#', '#\{\+[\w\_]+\}#'];
	private const REPLACE_PATTERN = ['(.+?)', '(\d+?)'];

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		if ($targetType instanceof SubtypeType && $targetType->name()->equals(new TypeNameIdentifier('RoutePattern'))) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof StringType || $parameterType instanceof StringSubsetType) {
				return $this->context->typeRegistry->union([$this->context->typeRegistry->map(
					$this->context->typeRegistry->union([
						$this->context->typeRegistry->string(),
						$this->context->typeRegistry->integer(0)
					]),
				), $this->context->typeRegistry->atom(new TypeNameIdentifier('RoutePatternDoesNotMatch'))]);
			}
			// @codeCoverageIgnoreStart
			throw new AnalyserException(sprintf("[%s] Invalid parameter type: %s", __CLASS__, $parameterType));
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new AnalyserException(sprintf("[%s] Invalid target type: %s", __CLASS__, $targetType));
		// @codeCoverageIgnoreEnd
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
		//$targetValue = $this->context->toBaseValue($targetValue);
		if ($targetValue instanceof SubtypeValue && $targetValue->type()->name()->equals(new TypeNameIdentifier('RoutePattern'))) {
			$pattern = $targetValue->baseValue()->literalValue();
			$parameter = $this->context->toBaseValue($parameter);
			if ($parameter instanceof StringValue) {
				$path = $parameter->literalValue();

				if (preg_match_all(self::ROUTE_PATTERN_MATCH, $pattern, $matches)) {
					$pathArgs = $matches[1] ?? [];
					$pattern = '^' . preg_replace(self::ROUTE_PATTERN_REPLACE, self::REPLACE_PATTERN, $pattern) . '$';
				} else {
					$pathArgs = null;
					$pattern = '^' . $pattern . '$';
				}
				$pattern = strtolower($pattern);
				if (!preg_match('#' . $pattern . '#', $path, $matches)) {
					return $this->context->valueRegistry->atom(new TypeNameIdentifier('RoutePatternDoesNotMatch'));
				}
				if (!is_array($pathArgs)) {
					return $this->context->valueRegistry->dict([]);
				}
				$values = [];
				$matchedValues = array_slice($matches, 1);
				foreach($pathArgs as $idx => $pathArg) {
					if ($pathArg[0] === '+') {
						$values[substr($pathArg, 1)] = $this->context->valueRegistry->integer(
							(int)$matchedValues[$idx]
						);
					} else {
						$values[$pathArg] = $this->context->valueRegistry->string(
							(string)$matchedValues[$idx]
						);
					}
				}
				return $this->context->valueRegistry->dict($values);
			}
			// @codeCoverageIgnoreStart
			throw new ExecutionException("Invalid parameter value");
			// @codeCoverageIgnoreEnd
		}
		// @codeCoverageIgnoreStart
		throw new ExecutionException("Invalid target value");
		// @codeCoverageIgnoreEnd
	}

}