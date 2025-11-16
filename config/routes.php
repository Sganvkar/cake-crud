<?php
/**
 * Routes configuration.
 *
 * This file defines how URLs in your application map
 * to controllers and actions.
 */

use Cake\Routing\Route\DashedRoute;     // Route class that converts dashed URLs to CamelCase
use Cake\Routing\RouteBuilder;          // Builder used to define routes

// The routes function is returned and executed by Application::routes()
return function (RouteBuilder $routes): void {

    // ----------------------------------------------------------------------
    // Set the default route class.
    // DashedRoute means URLs like /customer-orders will map to CustomerOrdersController.
    // ----------------------------------------------------------------------
    $routes->setRouteClass(DashedRoute::class);

    // ----------------------------------------------------------------------
    // Root URL scope: handles all routes starting with "/"
    // ----------------------------------------------------------------------
    $routes->scope('/', function (RouteBuilder $builder): void {

        // Make "/" load CustomersController::search()
        $builder->connect('/', [
            'controller' => 'Customers',
            'action'     => 'search'
        ]);

        // Keep pages route
        $builder->connect('/pages/*', 'Pages::display');

        $builder->fallbacks();
    });


    // ----------------------------------------------------------------------
    // UI route for the search screen
    // /customers/search → CustomersController::search()
    // ----------------------------------------------------------------------
    $routes->scope('/', function (RouteBuilder $builder): void {

        // When user visits /customers/search,
        // it loads templates/Customers/search.php
        $builder->connect('/customers/search', [
            'controller' => 'Customers',
            'action'     => 'search'
        ]);
    });

    $routes->prefix('Api', function (RouteBuilder $builder): void {

        $builder->connect('/customers/get', [
            'controller' => 'Customers',
            'action' => 'get'
        ])->setMethods(['POST']);

        $builder->fallbacks();
    });


    $routes->connect('/dbtest', ['controller' => 'Dbtest', 'action' => 'index']);
};
