<?php
$page = $_GET['page'] ?? 'index';
$routes = [
    'index'                     => 'modules/Patients/patients.php',
    'emergency'                 => 'modules/Emergency/emergency.php',
    'laboratory'                => 'modules/Laboratory/laboratory.php',
    'radiology'                 => 'modules/Radiology/radiology.php',
    'inventoryDrugsMeds'        => 'modules/Pharmacy/inventoryDrugsMeds.php',
    'issuedDrugsMeds'           => 'modules/Pharmacy/issuedDrugsMeds.php',
    'pulledoutDrugsMeds'        => 'modules/Pharmacy/pulledoutDrugsMeds.php',
    'inventorySupplies'         => 'modules/CentralSupply/inventorySupplies.php',
    'issuedSupplies'            => 'modules/CentralSupply/issuedSupplies.php',
    'pulledoutSupplies'         => 'modules/CentralSupply/pulledoutSupplies.php',
    'viewDashboard'             => 'modules/Medical_Records/viewDashboard.php',
    'admissionLog'              => 'modules/Medical_Records/admissionLog.php',
    'viewAdmissionLog'          => 'modules/Medical_Records/viewAdmissionLog.php'
    // 'erLog'                     => 'modules/Medical_Records/erLog.php',
    // 'viewERLog'                 => 'modules/Medical_Records/viewERLog.php',
    // 'opdLog'                    => 'modules/Medical_Records/opdLog.php',
    // 'viewOPDLog'                => 'modules/Medical_Records/viewOPDLog.php'
];
