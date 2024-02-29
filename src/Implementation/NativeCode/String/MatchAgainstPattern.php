<?php

namespace Walnut\Lang\Implementation\NativeCode\String;

use Walnut\Lang\Blueprint\Execution\AnalyserException;
use Walnut\Lang\Blueprint\Execution\ExecutionException;
use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Blueprint\Type\StringSubsetType;
use Walnut\Lang\Blueprint\Type\StringType;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\StringValue;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class MatchAgainstPattern implements Method {

	private const ROUTE_PATTERN_MATCH = '#\{([\w\_]+)\}#';
	private const ROUTE_PATTERN_REPLACE = '#\{[\w\_]+\}#';
	private const REPLACE_PATTERN = '(.+?)';

	public function __construct(
		private NativeCodeContext $context
	) {}

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		$targetType = $this->context->toBaseType($targetType);
		if ($targetType instanceof StringType || $targetType instanceof StringSubsetType) {
			$parameterType = $this->context->toBaseType($parameterType);
			if ($parameterType instanceof StringType || $parameterType instanceof StringSubsetType) {
				return $this->context->typeRegistry->union([
					$this->context->typeRegistry->map(
						$this->context->typeRegistry->string(),
					),
					$this->context->typeRegistry->false()
				]);
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
		$targetValue = $this->context->toBaseValue($targetValue);
		$parameter = $this->context->toBaseValue($parameter);
		if ($targetValue instanceof StringValue) {
			if ($parameter instanceof StringValue) {
				$target = $targetValue->literalValue();
				$path = $parameter->literalValue();

				if (preg_match_all(self::ROUTE_PATTERN_MATCH, $path, $matches)) {
					$pathArgs = $matches[1] ?? [];
					$path = '^' . preg_replace(self::ROUTE_PATTERN_REPLACE, self::REPLACE_PATTERN, $path) . '$';
				} else {
					$pathArgs = null;
					$path = '^' . $path . '$';
				}
				$path = strtolower($path);
				if (!preg_match('#' . $path . '#', $target, $matches)) {
					return $this->context->valueRegistry->false();
				}
				return is_array($pathArgs) ?
					$this->context->valueRegistry->dict(
						array_map(fn($value) =>
							$this->context->valueRegistry->string($value),
							array_combine(
								$pathArgs,
								array_slice($matches, 1)
							)
						)
					) :
					$this->context->valueRegistry->dict([]);
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