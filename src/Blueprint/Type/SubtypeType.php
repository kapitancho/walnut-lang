<?php

namespace Walnut\Lang\Blueprint\Type;

use Walnut\Lang\Blueprint\Function\FunctionBody;

interface SubtypeType extends NamedType {
    public function baseType(): Type;
    public function constructorBody(): FunctionBody;
	public function errorType(): Type|null;
}