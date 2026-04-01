<?php
require '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'supplyInventory':
        loadCSInventory($pdo);
        break;
    case 'pullout':
        pullOutDrug($pdo);
        break;
}

function loadCSInventory($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        $where = "WHERE 1=1
            AND hc2.isActive IS NOT NULL AND hc2.isActive != 'N'
        ";
        $params = [];

        // AND hc2.itemcode IS NOT NULL AND hc2.itemcode != ''
        // AND hc2.stockbal IS NOT NULL AND hc2.stockbal > 0

        if (!empty($startDate)) {
            $where .= " AND hc2.cl2dteas >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND hc2.cl2dteas <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        if (!empty($search)) {
            $where .= " AND (
                COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') LIKE :search
                OR h2.cl2desc LIKE :search

                OR (
                    CASE 
                        WHEN hc2.stockbal IS NULL OR hc2.stockbal = '0' THEN 'No Stock Balance'
                        ELSE hc2.stockbal
                    END
                ) LIKE :search
                OR COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') LIKE :search
                OR DATE(hc2.cl2dteas) LIKE :search
                OR DATE(hc2.expiry) LIKE :search
                OR DATE(hc2.cl2dtmd) LIKE :search
                OR chg.chrgdesc LIKE :search
                OR hc2.stockloc LIKE :search
                OR (
                    CASE
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 'EXPIRED'
                        WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                        WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END
                ) LIKE :search
            )";
            $params[':search'] = "%$search%";
        }

        $baseFrom = "
            FROM hclass2h hc2
            LEFT JOIN hclass2 h2
                ON h2.cl2comb = hc2.cl2comb
            LEFT JOIN hcharge chg 
                ON hc2.hclass2sub = chg.chrgcode
        ";

        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "
            SELECT
                COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') AS lot_number,

                CONCAT_WS(' ', h2.cl2desc, h2.uomcode) AS supply_name,

                hc2.cl2dtmd,
                hc2.itemcode,

                CASE 
                    WHEN hc2.stockbal IS NULL OR hc2.stockbal = '0' THEN 'No Stock Balance'
                    ELSE hc2.stockbal
                END AS stock_balance,
                hc2.begbal AS beg_balance,
                (hc2.begbal - hc2.stockbal) AS total_dispensed,
                
                COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') AS selling_price,
                
                hc2.cl2dteas AS entry_date,
                hc2.expiry AS expiry_date,
                hc2.cl2dtmd AS date_modified,
                chg.chrgdesc AS account_type,

                CASE
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                    WHEN hc2.expiry < CURDATE() THEN 'EXPIRED'
                    WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                    WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                    ELSE 'GOOD'
                END AS status,

                CASE
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 0
                    WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                    WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 3
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 4
                    ELSE 2
                END AS trigger_order,

                hc2.stockloc AS cost_center,
                hc2.delintkey AS id

            FROM hclass2h hc2

            LEFT JOIN hclass2 h2
                ON h2.cl2comb = hc2.cl2comb

            LEFT JOIN hcharge chg 
                ON hc2.hclass2sub = chg.chrgcode

            $where
        ";

        if ($length != -1) {
            $sql .= " LIMIT :start, :length";
        }

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSql = "
            SELECT
                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS totalStock,

                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS totalValue,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS expiredValue

            FROM hclass2h hc2

            LEFT JOIN hclass2 h2
                ON h2.cl2comb = hc2.cl2comb

            LEFT JOIN hcharge chg 
                ON hc2.hclass2sub = chg.chrgcode

            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($total),
            "data" => $data,
            "totals" => $totals
        ]);
    } catch (Exception $e) {

        echo json_encode([
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }

    exit;
}

function pullOutDrug($pdo)
{
    $cl2dtmd  = $_POST['cl2dtmd'] ?? '';
    $itemcode = $_POST['itemcode'] ?? '';
    $remarks  = $_POST['remarks'] ?? '';

    if (!$cl2dtmd || !$itemcode) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid item reference"
        ]);
        return;
    }

    if (empty($remarks)) {
        echo json_encode([
            "status" => "error",
            "message" => "Remarks is required"
        ]);
        return;
    }

    $sql = "UPDATE hclass2h
            SET isActive = 'N',
                remarks = :remarks
            WHERE cl2dtmd = :cl2dtmd
              AND itemcode = :itemcode
              AND isActive = 'Y'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cl2dtmd'  => $cl2dtmd,
        ':itemcode' => $itemcode,
        ':remarks'  => $remarks
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Item already pulled out or not found"
        ]);
    }
}
