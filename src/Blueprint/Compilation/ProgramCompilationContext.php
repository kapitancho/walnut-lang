<?php

namespace Walnut\Lang\Blueprint\Compilation;

use Walnut\Lang\Blueprint\Execution\Program;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;

interface ProgramCompilationContext {
    public function compileProgram(Source $source): Program;
    public function nativeCodeContext(): NativeCodeContext;
}