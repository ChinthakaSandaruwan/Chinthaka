<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RentFinder SL</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="../public/css/style.css" rel="stylesheet">

    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #333;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: #007bff;
        }

        .sidebar .nav-link.active {
            color: #007bff;
        }

        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .text-xs {
            font-size: 0.7rem;
        }

        .text-gray-300 {
            color: #dddfeb !important;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }

        .font-weight-bold {
            font-weight: 700 !important;
        }

        .font-weight-500 {
            font-weight: 500 !important;
        }

        .text-uppercase {
            text-transform: uppercase !important;
        }

        .shadow {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .h-100 {
            height: 100% !important;
        }

        .py-2 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        .no-gutters {
            margin-right: 0;
            margin-left: 0;
        }

        .no-gutters>.col,
        .no-gutters>[class*="col-"] {
            padding-right: 0;
            padding-left: 0;
        }

        .align-items-center {
            align-items: center !important;
        }

        .mr-2 {
            margin-right: 0.5rem !important;
        }

        .col-auto {
            flex: 0 0 auto;
            width: auto;
        }

        .h5 {
            font-size: 1.25rem;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }

        .mb-1 {
            margin-bottom: 0.25rem !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .pt-3 {
            padding-top: 1rem !important;
        }

        .pb-2 {
            padding-bottom: 0.5rem !important;
        }

        .px-md-4 {
            padding-left: 1.5rem !important;
            padding-right: 1.5rem !important;
        }

        .py-3 {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }

        .border-bottom {
            border-bottom: 1px solid #dee2e6 !important;
        }

        .d-flex {
            display: flex !important;
        }

        .justify-content-between {
            justify-content: space-between !important;
        }

        .flex-wrap {
            flex-wrap: wrap !important;
        }

        .flex-md-nowrap {
            flex-wrap: nowrap !important;
        }

        .align-items-center {
            align-items: center !important;
        }

        .btn-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .btn-group {
            position: relative;
            display: inline-flex;
            vertical-align: middle;
        }

        .btn-group .btn {
            position: relative;
            flex: 1 1 auto;
        }

        .btn-group .btn:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn-group .btn:not(:first-child) {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .me-2 {
            margin-right: 0.5rem !important;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }

        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-secondary:hover {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .flex-shrink-0 {
            flex-shrink: 0 !important;
        }

        .flex-grow-1 {
            flex-grow: 1 !important;
        }

        .ms-3 {
            margin-left: 1rem !important;
        }

        .fw-bold {
            font-weight: 700 !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .small {
            font-size: 0.875em;
        }

        .rounded-circle {
            border-radius: 50% !important;
        }

        .d-flex {
            display: flex !important;
        }

        .align-items-center {
            align-items: center !important;
        }

        .justify-content-center {
            justify-content: center !important;
        }

        .text-white {
            color: #fff !important;
        }

        .bg-primary {
            background-color: #007bff !important;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }
    </style>
</head>

<body>
    <!-- Unified Navigation -->
    <?php include '../includes/navbar.php'; ?>