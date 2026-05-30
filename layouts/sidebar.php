<?php
$currentPage = $_GET['page'] ?? 'index';

$isEmergencyOpen = ($currentPage == 'emergency');
$isLaboratoryOpen = ($currentPage == 'laboratory');
$isRadiologyOpen = ($currentPage == 'radiology');

$pharmacyPages = ['issuedDrugsMeds', 'issuedDrugsSupplies', 'inventoryDrugsMeds', 'pulledoutDrugsMeds'];
$isPharmacyOpen = in_array($currentPage, $pharmacyPages);
$inventoryDrugsMeds = ($currentPage == 'inventoryDrugsMeds');
$issuedDrugsMeds = ($currentPage == 'issuedDrugsMeds');
$issuedDrugsSupplies = ($currentPage == 'issuedDrugsSupplies');
$pulledoutDrugsMeds = ($currentPage == 'pulledoutDrugsMeds');

$suppliesPages = ['inventorySupplies', 'issuedSupplies', 'pulledoutSupplies'];
$isCentralSupplyOpen = in_array($currentPage, $suppliesPages);
$inventorySupplies = ($currentPage == 'inventorySupplies');
$issuedSupplies = ($currentPage == 'issuedSupplies');
$pulledoutSupplies = ($currentPage == 'pulledoutSupplies');

$stockRoomPages = ['inventoryStockRoom', 'issuedDMStockRoom', 'issuedSuppliesStockRoom'];
$isStockRoomOpen = in_array($currentPage, $stockRoomPages);
$inventoryStockRoom = ($currentPage == 'inventoryStockRoom');
$issuedDMStockRoom = ($currentPage == 'issuedDMStockRoom');
$issuedSuppliesStockRoom = ($currentPage == 'issuedSuppliesStockRoom');

$medicalRecordPages = ['admissionLog', 'viewAdmissionLog', 'viewDashboard', 'erLog', 'viewERLog', 'opdLog', 'viewOPDLog'];
$isMedicalRecordsOpen = in_array($currentPage, $medicalRecordPages);
$activeAdmission = in_array($currentPage, ['admissionLog', 'viewAdmissionLog']);
$activeER = in_array($currentPage, ['erLog', 'viewERLog']);
$activeOPD = in_array($currentPage, ['opdLog', 'viewOPDLog']);
$viewDashboard = ($currentPage == 'viewDashboard');
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
    <div class="position-sticky pt-3">
        <h6 class="sidebar-heading text-white px-3 mb-1 text-center"
            style="font-size: 0.60rem; text-transform: uppercase;">
            <?php
            $sql = "SELECT hhosname FROM hhospmas";
            $stmt = $pdo->query($sql);
            $hospitalName = $stmt->fetch(PDO::FETCH_ASSOC)['hhosname'] ?? 'HOSPITAL NAME';
            ?>
            <?= htmlspecialchars($hospitalName) ?>

        </h6>
        <h6 class="sidebar-heading text-white px-3 mt-4 mb-1"
            style="font-size: 0.60rem; text-transform: uppercase; background-color: #6c7973; color: #ffffff; padding: 0.5rem; border-radius: 4px;">
            REPORTS
        </h6>

        <ul class="nav flex-column">
            <!-- MASTERLIST -->
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage == 'index' ? 'active' : '' ?>" href="index.php">
                    Masterlist
                </a>
            </li>

            <!-- EMERGENCY -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#emergencySubmenu"
                    aria-expanded="<?= $isEmergencyOpen ? 'true' : 'false' ?>">
                    <span>Emergency</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse ps-3 <?= $isEmergencyOpen ? 'show' : '' ?>" id="emergencySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $currentPage == 'emergency' ? 'active' : '' ?>"
                                href="index.php?page=emergency">
                                Turnaround Time Report
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- LABORATORY -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#laboratorySubmenu"
                    aria-expanded="<?= $isLaboratoryOpen ? 'true' : 'false' ?>">
                    <span>Laboratory</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse ps-3 <?= $isLaboratoryOpen ? 'show' : '' ?>" id="laboratorySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $currentPage == 'laboratory' ? 'active' : '' ?>"
                                href="index.php?page=laboratory">
                                Turnaround Time Report
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- RADIOLOGY -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#radiologySubmenu"
                    aria-expanded="<?= $isRadiologyOpen ? 'true' : 'false' ?>">
                    <span>Radiology</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>

                <div class="collapse ps-3 <?= $isRadiologyOpen ? 'show' : '' ?>" id="radiologySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $currentPage == 'radiology' ? 'active' : '' ?>"
                                href="index.php?page=radiology">
                                Turnaround Time Report
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- PHARMACY -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#pharmacySubmenu"
                    aria-expanded="<?= $isPharmacyOpen ? 'true' : 'false' ?>">
                    <span>Pharmacy</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse ps-3 <?= $isPharmacyOpen ? 'show' : '' ?>" id="pharmacySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $inventoryDrugsMeds ? 'active' : '' ?>"
                                href="index.php?page=inventoryDrugsMeds">
                                Inventory of Drugs and Medicines
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $issuedDrugsMeds ? 'active' : '' ?>"
                                href="index.php?page=issuedDrugsMeds">
                                Issued Drugs and Medicines
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $pulledoutDrugsMeds ? 'active' : '' ?>" href="index.php?page=pulledoutDrugsMeds">
                                Pulled Out Drugs and Medicines
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $issuedDrugsSupplies ? 'active' : '' ?>"
                                href="index.php?page=issuedDrugsSupplies">
                                Issued Non-Drugs and Supplies (Pharmaceutical)
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- CENTRAL SUPPLY -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#centralSupplySubmenu"
                    aria-expanded="<?= $isCentralSupplyOpen  ? 'true' : 'false' ?>">
                    <span>Central Supply</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse ps-3 <?= $isCentralSupplyOpen ? 'show' : '' ?>" id="centralSupplySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $inventorySupplies ? 'active' : '' ?>"
                                href="index.php?page=inventorySupplies">
                                Inventory of Supplies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $issuedSupplies ? 'active' : '' ?>"
                                href="index.php?page=issuedSupplies">
                                Issued Non-Drugs and Supplies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $pulledoutSupplies ? 'active' : '' ?>"
                                href="index.php?page=pulledoutSupplies">
                                Pulled Out Non-Drugs and Supplies
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- STOCKROOM -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#stockRoomSubmenu"
                    aria-expanded="<?= $isStockRoomOpen ? 'true' : 'false' ?>">
                    <span>Stock Room</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse ps-3 <?= $isStockRoomOpen ? 'show' : '' ?>" id="stockRoomSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $inventoryStockRoom ? 'active' : '' ?>"
                                href="index.php?page=inventoryStockRoom">
                                Inventory of Items
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $issuedDMStockRoom ? 'active' : '' ?>"
                                href="index.php?page=issuedDMStockRoom">
                                Issued Drugs and Medicines to Pharmacy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $issuedSuppliesStockRoom ? 'active' : '' ?>"
                                href="index.php?page=issuedSuppliesStockRoom">
                                Issued Non-Drugs and Supplies to Central Supply
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- MEDICAL RECORDS -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex justify-content-between align-items-center"
                    data-bs-toggle="collapse"
                    href="#medicalRecordsSubmenu"
                    aria-expanded="<?= $isMedicalRecordsOpen ? 'true' : 'false' ?>">
                    <span>Medical Records</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse ps-3 <?= $isMedicalRecordsOpen ? 'show' : '' ?>" id="medicalRecordsSubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $viewDashboard ? 'active' : '' ?>"
                                href="index.php?page=viewDashboard">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $activeAdmission ? 'active' : '' ?>"
                                href="index.php?page=admissionLog">
                                <i class="fa-solid fa-hospital-user"></i> Admission Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $activeER ? 'active' : '' ?>"
                                href="index.php?page=erLog">
                                <i class="fa-solid fa-heartbeat"></i> Emergency Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $activeOPD ? 'active' : '' ?>"
                                href="index.php?page=opdLog">
                                <i class="fa-solid fa-user-md"></i> Out-Patient Log
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</nav>

<!-- <style>
    #sidebarMenu {
        height: 100vh;
        /* Full screen height */
        overflow-y: auto;
        /* Vertical scroll */
        overflow-x: hidden;
        /* Prevent horizontal scroll */
        position: fixed;
        /* Keeps sidebar fixed while page scrolls */
        top: 0;
        left: 0;
    }

    /* Optional: smoother scrollbar */
    #sidebarMenu::-webkit-scrollbar {
        width: 8px;
    }

    #sidebarMenu::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 10px;
    }

    #sidebarMenu::-webkit-scrollbar-track {
        background: #343a40;
    }
</style> -->