<?php
$pageTitle = "Medical Records Report";

$hpercode = $_GET['hpercode'] ?? '';

$sql = "
SELECT
    h1.hpercode,

    CONCAT(
        p.patlast, ', ',
        p.patfirst,
        CASE
            WHEN p.patsuffix IS NULL OR p.patsuffix IN ('NOTAP','N/A') THEN ''
            ELSE CONCAT(' ', p.patsuffix)
        END,
        CASE
            WHEN p.patmiddle IS NULL OR p.patmiddle IN ('','N/A') THEN ''
            ELSE CONCAT(' ', p.patmiddle)
        END
    ) AS patient,

    h1.enccode AS encounter,
    DATE_FORMAT(h1.admdate, '%Y-%m-%d %H:%i') AS admission_date,
    DATE_FORMAT(h1.disdate, '%Y-%m-%d %H:%i') AS discharge_date,
    
    h2.enccode AS next_encounter,
    DATE_FORMAT(h2.admdate, '%Y-%m-%d %H:%i') AS next_admission,
    DATE_FORMAT(h2.disdate, '%Y-%m-%d %H:%i') AS next_discharge,

    TIMESTAMPDIFF(DAY, h1.disdate, h2.admdate) AS days_before_readmission

FROM hadmlog h1
LEFT JOIN hadmlog h2
    ON h1.hpercode = h2.hpercode
    AND h2.admdate > h1.disdate
    AND NOT EXISTS (
        -- pick the **closest next admission**
        SELECT 1 FROM hadmlog h3
        WHERE h3.hpercode = h1.hpercode
          AND h3.admdate > h1.disdate
          AND h3.admdate < h2.admdate
    )
JOIN hperson p
    ON h1.hpercode = p.hpercode

WHERE h1.hpercode = :hpercode
ORDER BY h1.admdate
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['hpercode' => $hpercode]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <span class="navbar-brand mb-0 h1 text-white ">
        MEDICAL RECORDS - ADMISSION LOG
    </span>
</nav>

<div class="px-md-3">
    <a href="index.php?page=admissionLog" style="font-size: 0.6rem;">
        BACK TO ADMISSION LOG
    </a>
</div>
<br>

<div class="px-md-3">
    <div class="alert alert-warning alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This table shows the complete admission history of the patient. Use the search box to filter by <b>Admission Date</b> or <b>Discharge Date</b>.</p>
    </div>
</div>

<h6 class="px-md-3 text-muted">PATIENT'S HISTORY</h6>

<div class="px-md-1">
    <div class="table-container">
        <table id="viewAdmissionLogTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>PATIENT</th>
                    <th>ADMISSION DATE</th>
                    <th>DISCHARGE DATE</th>
                    <th>DAYS BEFORE READMISSION</th>
                    <th>NEXT ADMISSION</th>
                    <th>NEXT DISCHARGE</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['patient']) ?></td>
                        <td><?= htmlspecialchars($row['admission_date']) ?></td>
                        <td><?= htmlspecialchars($row['discharge_date']) ?></td>
                        <td><?= htmlspecialchars($row['days_before_readmission'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['next_admission'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['next_discharge'] ?? '-') ?></td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>