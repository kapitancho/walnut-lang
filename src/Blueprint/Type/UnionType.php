<?php

namespace Walnut\Lang\Blueprint\Type;

interface UnionType extends Type {
    /**
     * @return list<Type>
     */
    public function types(): array;
}