<?php declare(strict_types=1);

return static function(FastRoute\RouteCollector $routeCollector) {
    $routeCollector->addRoute(
        'GET',
        '/',
        [\Movary\HttpController\DashboardController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/settings',
        [\Movary\HttpController\SettingsController::class, 'render']
    );
    $routeCollector->addRoute(
        'GET',
        '/history',
        [\Movary\HttpController\HistoryController::class, 'render']
    );
    $routeCollector->addRoute(
        'POST',
        '/letterboxd-rating',
        [\Movary\HttpController\Letterboxd::class, 'uploadRatingCsv']
    );
    $routeCollector->addRoute(
        'POST',
        '/refresh-trakt',
        [\Movary\HttpController\SyncTraktController::class, 'execute']
    );
    $routeCollector->addRoute(
        'POST',
        '/refresh-tmdb',
        [\Movary\HttpController\SyncTmdbController::class, 'execute']
    );
    $routeCollector->addRoute(
        'POST',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'login']
    );
    $routeCollector->addRoute(
        'GET',
        '/login',
        [\Movary\HttpController\AuthenticationController::class, 'renderLoginPage']
    );
    $routeCollector->addRoute(
        'GET',
        '/logout',
        [\Movary\HttpController\AuthenticationController::class, 'logout']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/history',
        [\Movary\HttpController\MovieHistoryController::class, 'fetchHistory']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedMovies',
        [\Movary\HttpController\MostWatchedMoviesController::class, 'fetchMostWatchedMovies']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedActors',
        [\Movary\HttpController\MostWatchedActorsController::class, 'fetchMostWatchedActors']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedGenres',
        [\Movary\HttpController\MostWatchedGenresController::class, 'fetchMostWatchedGenres']
    );
    $routeCollector->addRoute(
        'GET',
        '/api/mostWatchedProductionCompanies',
        [\Movary\HttpController\MostWatchedProductionCompaniesController::class, 'fetchMostWatchedProductionCompanies']
    );
};
