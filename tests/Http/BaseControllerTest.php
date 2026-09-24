<?php

declare(strict_types=1);

use Codefy\Framework\Contracts\RoutingController;
use Codefy\Framework\Http\BaseController;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\View\Renderer;

it('resolves a controller and redirects without service bindings', function () {
    $controller = new Injector(InjectorFactory::create())->make(BaseController::class);
    $response = $controller->redirect('/login');

    expect($controller)->toBeInstanceOf(RoutingController::class)
        ->and($response->getStatusCode())->toBe(302)
        ->and($response->getHeaderLine('Location'))->toBe('/login')
        ->and($controller->redirect('/saved', 303)->getStatusCode())->toBe(303);
});

it('allows a child constructor to register middleware without parent dependencies', function () {
    $controller = new class extends BaseController {
        public function __construct()
        {
            $this->middleware('auth')->only(['show']);
        }
    };

    $middleware = $controller->getControllerMiddleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0]->middleware())->toBe('auth')
        ->and($middleware[0]->excludedForMethod('show'))->toBeFalse()
        ->and($middleware[0]->excludedForMethod('index'))->toBeTrue()
        ->and(new BaseController()->getControllerMiddleware())->toBe([]);
});

it('allows a child to inject only its renderer', function () {
    $view = new class implements Renderer {
    };
    $controller = new class ($view) extends BaseController {
        public function __construct(protected Renderer $view)
        {
        }

        public function renderer(): Renderer
        {
            return $this->view;
        }
    };

    expect($controller->renderer())->toBe($view);
});

it('retains fluent view injection without a constructor', function () {
    $controller = new class extends BaseController {
        public function renderer(): Renderer
        {
            return $this->view;
        }
    };
    $view = new class implements Renderer {
    };
    $replacement = new class implements Renderer {
    };

    expect($controller->setView($view))->toBe($controller)
        ->and($controller->renderer())->toBe($view)
        ->and($controller->setView($replacement))->toBe($controller)
        ->and($controller->renderer())->toBe($replacement);
});
