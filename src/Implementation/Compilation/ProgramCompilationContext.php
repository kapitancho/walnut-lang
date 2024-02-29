<?php

namespace Walnut\Lang\Implementation\Compilation;

use Walnut\Lang\Blueprint\Compilation\ProgramCompilationContext as ProgramCompilationContextInterface;
use Walnut\Lang\Blueprint\Compilation\Source;
use Walnut\Lang\Blueprint\Execution\Program;
use Walnut\Lang\Blueprint\NativeCode\NativeCodeContext;
use Walnut\Lang\Implementation\Registry\ProgramBuilderFactory;

final readonly class ProgramCompilationContext implements ProgramCompilationContextInterface {

    public function __construct(
        private ProgramBuilderFactory $programBuilderFactory
    ) {}

    public function nativeCodeContext(): NativeCodeContext {
        return new NativeCodeContext(
            $this->programBuilderFactory->typeRegistry,
            $this->programBuilderFactory->valueRegistry
        );
    }

    public function compileProgram(Source $source): Program {
        $pb = $this->programBuilderFactory->getProgramBuilder();
        $logger = new TransitionLogger();
        $moduleImporter = new ModuleImporter($source->sourceRoot, $pb, $logger);
        $moduleImporter->importModule($source->startModuleName);
        return $pb->build();
    }
}