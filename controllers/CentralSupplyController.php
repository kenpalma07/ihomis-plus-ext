<?php
require '../db.ph';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'inventory':
        loadInventory($pdo);
        break;
}

function loadInventory($pdo)
{
    try {
        $draw   = $_POST['draw']
    } catch (Exception $e) {

    }

    exit;
}

?>