<?php
$currentPage = $_GET['page'] ?? 'index';

$isEmergencyOpen = ($currentPage == 'emergency');
$isLaboratoryOpen = ($currentPage == 'laboratory');
$isRadiologyOpen = ($currentPage == 'radiology');

$pharmacyPages = ['issuedDrugsMeds', 'issuedDrugsSupplies', 'inventoryDrugsMeds'];
$isPharmacyOpen = in_array($currentPage, $pharmacyPages);
$inventoryDrugsMeds = ($currentPage == 'inventoryDrugsMeds');
$issuedDrugsMeds = ($currentPage == 'issuedDrugsMeds');
$issuedDrugsSupplies = ($currentPage == 'issuedDrugsSupplies');

$suppliesPages = ['inventorySupplies'];
$isCentralSupplyOpen = in_array($currentPage, $suppliesPages);
$inventorySupplies = ($currentPage == 'inventorySupplies');

$medicalRecordPages = ['admissionLog', 'viewAdmissionLog', 'viewDashboard'];
$isMedicalRecordsOpen = in_array($currentPage, $medicalRecordPages);
$activeAdmission = in_array($currentPage, ['admissionLog', 'viewAdmissionLog']);
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
                            <a class="nav-link text-white <?= $issuedDrugsSupplies ? 'active' : '' ?>"
                                href="index.php?page=issuedDrugsSupplies">
                                Issued Non-Drugs and Supplies (Pharmacy)
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
                    aria-expanded="<?= $inventorySupplies ? 'true' : 'false' ?>">
                    <span>Central Supply</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse ps-3 <?= $inventorySupplies ? 'show' : '' ?>" id="centralSupplySubmenu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white <?= $inventorySupplies ? 'active' : '' ?>"
                                href="index.php?page=inventorySupplies">
                                Inventory of Supplies
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
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</nav>