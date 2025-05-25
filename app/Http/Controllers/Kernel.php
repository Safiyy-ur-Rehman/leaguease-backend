<php?

protected $middleware = [
    // ...
    \Illuminate\Http\Middleware\HandleCors::class,
    // ...
];

protected $middlewareGroups = [
    'web' => [
        // Web middleware group
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ... other middleware
    ],
];