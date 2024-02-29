<?php

namespace Walnut\Lang\Blueprint\Type;

interface StateType extends NamedType {
    public function stateType(): Type;
}