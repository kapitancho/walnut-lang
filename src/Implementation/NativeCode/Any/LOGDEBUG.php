<?php /** @noinspection SpellCheckingInspection */

namespace Walnut\Lang\Implementation\NativeCode\Any;

use Walnut\Lang\Blueprint\Function\Method;
use Walnut\Lang\Blueprint\Type\Type;
use Walnut\Lang\Blueprint\Value\Value;

final readonly class LOGDEBUG implements Method {

	public function analyse(
		Type $targetType,
		Type $parameterType,
		Type|null $dependencyType,
	): Type {
		return $targetType;
	}

	public function execute(
		Value $targetValue,
		Value $parameter,
		Value|null $dependencyValue,
	): Value {
        file_put_contents(__DIR__ . '/../../../../../../log/nut.log', $targetValue . '\n\n', FILE_APPEND);
		return $targetValue;
	}

}