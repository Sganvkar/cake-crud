<?php
declare(strict_types=1); 
// Strict typing ensures PHP enforces correct data types in function arguments and return values.

namespace App\Controller;
// Declares the namespace. This controller lives in src/Controller.

use Cake\Http\Response;
// Import Response class so the method signature (?Response) can be used.

class CustomersController extends AppController
// This controller extends AppController which provides helpers, components and shared logic.
{
    public function initialize(): void
    // Runs automatically before every action in this controller.
    {
        parent::initialize(); 
        // Calls AppController's initialize() so you inherit its configurations.

        $this->viewBuilder()->setLayout('default');
        // Sets the layout file used when rendering views.
        // Layout file: templates/layout/default.php
        // This ensures the search page is wrapped inside your default layout HTML.
    }

    // GET /customers/search
    public function search(): ?Response
    // This action handles the UI page where user selects fields + filters.
    // It returns a Response or null (null means Cake will render the view automatically).
    {
        // Renders templates/Customers/search.php
        // Because the method returns null, CakePHP automatically looks for:
        // templates/Customers/search.php
        // No need to call $this->render() manually.

        return null; 
        // Explicit null return indicates: "Render the view normally."
    }
}
