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
use Cake\Log\Log;

class CustomersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->Customers = $this->fetchTable('Customers');

        // Only allow POST requests
        $this->request->allowMethod(['post']);

        // We are generating XML manually
        $this->autoRender = false;
    }


    public function get(): Response
    {
        // Read raw XML
        $raw = $this->getRequest()->getBody()->getContents();
        Log::write('debug', $raw ?: 'EMPTY BODY RECEIVED');

        if (empty($raw)) {
            return $this->xmlError("No request body");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw);

        if ($xml === false) {
            return $this->xmlError("Invalid XML");
        }

        // --------------------------------------------------------------
        // Extract requested fields
        // --------------------------------------------------------------
        $requestedFields = [];

        if (isset($xml->RequestFields->Customers->Customer)) {
            foreach ($xml->RequestFields->Customers->Customer->children() as $child) {
                $requestedFields[] = $child->getName();
            }
        }

        // Default fields if none selected
        if (empty($requestedFields)) {
            $requestedFields = [
                'BillTo',
                'CreatedDate',
                'CustomerCode',
                'CustomerName',
                'WarehouseCode'
            ];
        }

        // --------------------------------------------------------------
        // ALWAYS include "id" internally
        // (but do NOT require it in RequestFields)
        // --------------------------------------------------------------
        $dbFields = array_unique(
            array_merge(['id'], $requestedFields)
        );

        // --------------------------------------------------------------
        // Build filters
        // --------------------------------------------------------------
        $conditions = [];

        if (isset($xml->Filters)) {
            foreach ($xml->Filters->children() as $field) {
                $fieldName = $field->getName();
                $like     = (string)$field->Like;
                $notlike  = (string)$field->NotLike;

                if ($like !== '') {
                    $conditions[] = [$fieldName . ' LIKE' => $like];
                }
                if ($notlike !== '') {
                    $conditions[] = [$fieldName . ' NOT LIKE' => $notlike];
                }
            }
        }

        // --------------------------------------------------------------
        // Record limit
        // --------------------------------------------------------------
        $limit = null;
        if (isset($xml->RecordLimit)) {
            $limit = (int)$xml->RecordLimit;
            if ($limit <= 0) {
                $limit = null;
            }
        }

        // --------------------------------------------------------------
        // Fetch from DB
        // --------------------------------------------------------------
        $customersTable = $this->getTableLocator()->get('Customers');

        $query = $customersTable->find()->select($dbFields);

        foreach ($conditions as $c) {
            $query->where($c);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $results = $query->all()->toArray();

        // --------------------------------------------------------------
        // Build XML Response
        // --------------------------------------------------------------
        $resp = new \SimpleXMLElement('<CusGetCustomersResponse/>');

        $status = $resp->addChild('APIResponseStatus');
        $status->addChild('Code', 'OK');

        $customersNode = $resp->addChild('Customers');

        foreach ($results as $row) {

            // Each <Customer>
            $customerNode = $customersNode->addChild('Customer');

            // Always include ID in the response
            $customerNode->addChild('id', (string)$row->id);

            // Add only requested fields
            foreach ($requestedFields as $f) {

                $value = $row->{$f} ?? null;

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                }

                $customerNode->addChild(
                    $f,
                    htmlspecialchars((string)$value)
                );
            }
        }

        return $this->getResponse()
            ->withType('application/xml')
            ->withStringBody($resp->asXML());
    }

    /**
     * Helper for quick XML error responses
     */
    private function xmlError(string $msg): Response
    {
        $xml = "<CusGetCustomersResponse>
                    <APIResponseStatus>
                        <Code>ERROR</Code>
                        <Message>{$msg}</Message>
                    </APIResponseStatus>
                </CusGetCustomersResponse>";

        return $this->getResponse()
            ->withType('application/xml')
            ->withStringBody($xml);
    }


}
