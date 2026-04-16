<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

/* =========================
   HEADERS (Excel)
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=cs_issued_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   INPUT FILTERS (same as DataTable)
========================= */
$startDate = $_GET['startDate'] ?? '';
$endDate   = $_GET['endDate'] ?? '';
$search    = $_GET['search'] ?? '';

/* =========================
   WHERE CONDITIONS
========================= */
$where = "WHERE 1=1";
$params = [];

if (!empty($startDate) && !empty($endDate)) {
   $where .= " AND hrq.issuedte BETWEEN :startDate AND :endDate";
   $params[':startDate'] = $startDate . " 00:00:00";
   $params[':endDate'] = $endDate . " 23:59:59";
}

if (!empty($search)) {
   $where .= " AND (
      hrq.itemcode LIKE :search
      OR hc.cl2desc LIKE :search
      OR hp.hpercode LIKE :search

      OR CONCAT(hp.patfirst, ' ', hp.patlast) LIKE :search
      OR CONCAT(hp.patlast, ', ', hp.patfirst) LIKE :search
      OR CONCAT(hp.patfirst, ' ', hp.patlast, ' ', hp.patmiddle) LIKE :search
      OR CONCAT(hp.patlast, ', ', hp.patfirst, ' ', hp.patmiddle) LIKE :search
      OR hp.patlast LIKE :search
      OR hp.patfirst LIKE :search
      OR hp.patmiddle LIKE :search

      OR hrq.pcchrgcode LIKE :search
      OR chg.chrgdesc LIKE :search
      OR CONCAT(hpl.lastname, ', ', hpl.firstname) LIKE :search
      OR hpl.firstname LIKE :search
      OR hpl.lastname LIKE :search
   )";
}

$sql ="

";