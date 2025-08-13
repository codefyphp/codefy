<?php

use Codefy\Framework\Application;
use Qubus\Exception\Data\TypeException;

try {
    return Application::getInstance(dirname(__DIR__, 2));
} catch (TypeException $e) {
    return $e->getMessage();
}
