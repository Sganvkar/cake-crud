<?php
declare(strict_types=1);
// Enforces strict typing — PHP will require correct data types.

namespace App\Controller\Api;
// This controller belongs to the Api namespace.
// File must be located at: src/Controller/Api/CustomersController.php

use App\Controller\AppController;            // Base controller for shared logic
use Cake\Http\Response;                     // Response class for return typing
use Cake\Datasource\ConnectionManager;      // (Not used now, but available)
use Cake\Utility\Text;                      // (Not used now)

class CustomersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // Runs parent's initialization (components, helpers, etc.)

        // Allow XML rendering capability if needed through RequestHandler.
        // Maps 'xml' to Cake's XmlView (not used for this manual XML output).
        $this->RequestHandler->setConfig('viewClassMap', [
            'xml' => 'Cake\View\XmlView'
        ]);

        // Load the CustomersTable model (src/Model/Table/CustomersTable.php)
        $this->loadModel('Customers');

        // Only allow POST requests for this controller action (security).
        $this->getRequest()->allowMethod(['post']);

        // Disable auto-rendering of templates.
        // We will return raw XML manually using ->withStringBody()
        $this->autoRender = false;
    }

    public function get(): Response
    // This method handles: POST /api/customers/get
    // It receives XML, parses filters, queries the DB, returns XML.
    {
        // Fetch raw XML body from the incoming request.
        $raw = (string)$this->getRequest()->getInput();

        // If the request body is empty, return an error XML response.
        if (empty($raw)) {
            return $this->getResponse()
                ->withType('application/xml')
                ->withStringBody(
                    '<CusGetCustomersResponse>
                        <APIResponseStatus>
                            <Code>ERROR</Code>
                            <Message>No request body</Message>
                        </APIResponseStatus>
                    </CusGetCustomersResponse>'
                );
        }

        // Enable internal XML error handling instead of warnings.
        libxml_use_internal_errors(true);

        // Parse XML into SimpleXMLElement
        $xml = simplexml_load_string($raw);

        // If parsing fails, return invalid XML error.
        if ($xml === false) {
            $msg = 'Invalid XML';
            return $this->getResponse()
                ->withType('application/xml')
                ->withStringBody(
                    "<CusGetCustomersResponse>
                        <APIResponseStatus>
                            <Code>ERROR</Code>
                            <Message>{$msg}</Message>
                        </APIResponseStatus>
                    </CusGetCustomersResponse>"
                );
        }

        // --------------------------------------------------------------
        // Extract requested fields from <RequestFields>
        // --------------------------------------------------------------
        $requestedFields = [];

        // Check if <RequestFields><Customers><Customer> exists
        if (isset($xml->RequestFields->Customers->Customer)) {

            // Loop over each child node (<BillTo/>, <CustomerName/>, etc.)
            foreach ($xml->RequestFields->Customers->Customer->children() as $child) {

                // Get XML tag name (e.g., "BillTo")
                $name = $child->getName();

                // Add to list
                $requestedFields[] = $name;
            }
        }

        // If no fields were selected, default to ALL fields.
        if (empty($requestedFields)) {
            $requestedFields = [
                'BillTo',
                'CreatedDate',
                'CustomerCode',
                'CustomerName',
                'WarehouseCode'
            ];
        }

        // For simplicity, DB column names match XML field names.
        // If DB uses snake_case, create a mapping table.
        $dbFields = $requestedFields;

        // --------------------------------------------------------------
        // Build SQL conditions from <Filters>
        // --------------------------------------------------------------
        $conditions = [];

        if (isset($xml->Filters)) {

            // Iterate through each filter node (<BillTo>, <CustomerCode>, etc.)
            foreach ($xml->Filters->children() as $field) {

                $fieldName = $field->getName();  // e.g. BillTo
                $like     = (string)$field->Like;
                $notlike  = (string)$field->NotLike;

                // Add LIKE condition if present
                if ($like !== '') {
                    $conditions[] = [$fieldName . ' LIKE' => $like];
                }

                // Add NOT LIKE condition if present
                if ($notlike !== '') {
                    $conditions[] = [$fieldName . ' NOT LIKE' => $notlike];
                }
            }
        }

        // --------------------------------------------------------------
        // Extract RecordLimit if provided
        // --------------------------------------------------------------
        $limit = null;

        if (isset($xml->RecordLimit)) {
            $limit = (int)$xml->RecordLimit;

            // Ignore invalid limits (zero or negative)
            if ($limit <= 0) {
                $limit = null;
            }
        }

        // --------------------------------------------------------------
        // Run database query
        // --------------------------------------------------------------
        $customersTable = $this->getTableLocator()->get('Customers');

        // Start query selecting only requested DB fields
        $query = $customersTable->find()->select($dbFields);

        // Apply each condition
        foreach ($conditions as $c) {
            $query = $query->where($c);
        }

        // Apply limit if specified
        if ($limit) {
            $query = $query->limit($limit);
        }

        // Execute query + fetch results as array
        $results = $query->all()->toArray();

        // --------------------------------------------------------------
        // Build XML Response
        // --------------------------------------------------------------
        $resp = new \SimpleXMLElement('<CusGetCustomersResponse/>');

        // Add API response status
        $status = $resp->addChild('APIResponseStatus');
        $status->addChild('Code', 'OK');

        // Add Customers container
        $customersNode = $resp->addChild('Customers');

        // Loop through DB rows
        foreach ($results as $row) {

            $customerNode = $customersNode->addChild('Customer');

            // Loop through selected fields and add them to XML
            foreach ($dbFields as $f) {

                $value = null;

                // Try exact property name (CamelCase)
                if (isset($row->{$f})) {
                    $value = $row->{$f};

                } else {
                    // Try lowercase as fallback
                    $lc = strtolower($f);

                    if (isset($row->{$lc})) {
                        $value = $row->{$lc};
                    }
                }

                // Format dates properly
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                }

                // Write XML node with value
                $customerNode->addChild(
                    $f,
                    htmlspecialchars((string)$value)
                );
            }
        }

        // Convert SimpleXMLElement to XML string
        $body = $resp->asXML();

        // Return XML as HTTP response
        return $this->getResponse()
            ->withType('application/xml')          // Set Content-Type header
            ->withStringBody($body);               // Set response XML body
    }
}
