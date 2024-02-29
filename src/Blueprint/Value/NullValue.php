<?php

namespace Walnut\Lang\Blueprint\Value;

interface NullValue extends AtomValue, LiteralValue {
    public function literalValue(): null;
}