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

        // Connect the home page "/" to PagesController::display('home')
        // So visiting "/" loads templates/Pages/home.php
        $builder->connect('/', [
            'controller' => 'Pages',
            'action'    => 'display',
            'home'      // extra parameter passed to the action
        ]);

        // Connect "/pages/*" URLs to PagesController::display()
        // Allows URLs like /pages/about or /pages/contact
        $builder->connect('/pages/*', 'Pages::display');

        // Create fallback routes:
        // - /controller → index()
        // - /controller/action/* → action with parameters
        // Good for early development but not recommended long-term.
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
