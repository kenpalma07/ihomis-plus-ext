<?php
$page = $_GET['page'] ?? 'index';
$routes = [
    'index'                     => 'modules/Patients/patients.php',
    'emergency'                 => 'modules/Emergency/emergency.php',
    'laboratory'                => 'modules/Laboratory/laboratory.php',
    'radiology'                 => 'modules/Radiology/radiology.php',
    'inventoryDrugsMeds'        => 'modules/Pharmacy/inventoryDrugsMeds.php',
    'issuedDrugsMeds'           => 'modules/Pharmacy/issuedDrugsMeds.php',
    //'exportIssuedDrugs'       => 'modules/Pharmacy/export_issued_drugs.php',
    'pulledoutDrugsMeds'        => 'modules/Pharmacy/pulledoutDrugsMeds.php',
    'inventorySupplies'         => 'modules/CentralSupply/inventorySupplies.php',
    'viewDashboard'             => 'modules/Medical_Records/viewDashboard.php',
    'admissionLog'              => 'modules/Medical_Records/admissionLog.php',
    'viewAdmissionLog'          => 'modules/Medical_Records/viewAdmissionLog.php'
];
