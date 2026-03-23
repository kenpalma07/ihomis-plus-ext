<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: Arial;
            font-size: 13px;
            margin: 0;
        }

        .dataTables_wrapper {
            font-size: 10px;
        }

        .table-container {
            max-width: 1200px;
            margin: auto;
        }

        .table {
            font-size: 9.1px;
        }

        .sidebar {
            min-height: 100vh;
        }

        .sidebar .nav-link {
            font-size: 14px;
        }

        .sidebar .nav-link.active {
            background-color: #198754;
            border-radius: 5px;
        }

        .nav-link[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
            transition: 0.3s;
        }

        .turnaround-danger {
            background-color: #ff6473 !important;
            color: white !important;
            font-weight: bold;
        }

        .alert-compact-left {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            padding: 0.5rem 0.8rem !important;
            height: auto !important;
            font-size: 0.60rem !important;
            line-height: 1.4 !important;
            border-radius: 0.25rem !important;
        }

        .disabled-link {
            pointer-events: none;
            /* prevents clicks */
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

</head>

<body>

    <div class="container-fluid">
        <div class="row">