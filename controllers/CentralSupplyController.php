<?php
require '../db.ph';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'inventory':
        loadCSInventory($pdo);
        break;
}

function loadCSInventory($pdo) {}
