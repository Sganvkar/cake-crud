<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;

class DbtestController extends AppController
{
    public function index()
    {
        $conn = ConnectionManager::get('default');

        try {
            $rows = $conn->execute('SELECT 1 AS test')->fetchAll('assoc');
            dd($rows); // dump and die
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
