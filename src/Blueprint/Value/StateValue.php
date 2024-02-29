<?php

namespace Walnut\Lang\Blueprint\Value;

use Walnut\Lang\Blueprint\Type\StateType;

interface StateValue extends Value {
    public function type(): StateType;
    public function stateValue(): Value;
}