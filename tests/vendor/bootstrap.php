<?php

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;

try {
    return Application::create(['basePath' => dirname(path: __DIR__, levels: 2)])
            ->withKernels()
            ->withProviders([
                // fill in custom providers
            ])
            ->withSingletons([
                // fill in custom singletons
            ])
            ->return();
} catch (TypeException $e) {
    return $e->getMessage();
}
