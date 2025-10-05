<?php

/**
 * Performance Monitor - Monitor application performance
 * Access: http://localhost/chinthaka/logs/monitor_performance.php
 */

// Simple authentication
$logPassword = 'admin123'; // Same password as log viewer
$authenticated = false;

if (isset($_POST['password']) && $_POST['password'] === $logPassword) {
    $authenticated = true;
    setcookie('perf_auth', '1', time() + 3600);
} elseif (isset($_COOKIE['perf_auth']) && $_COOKIE['perf_auth'] === '1') {
    $authenticated = true;
}

if (!$authenticated) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Performance Monitor - Authentication</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Performance Monitor Authentication</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Get system information
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

// Get database connection info
include '../config/database.php';
$dbStatus = 'Connected';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $dbStatus = 'Error: ' . $e->getMessage();
    $userCount = 0;
}

// Get log file sizes
$errorLogSize = file_exists('error.log') ? filesize('error.log') : 0;
$perfLogSize = file_exists('performance.log') ? filesize('performance.log') : 0;
$queryLogSize = file_exists('queries.log') ? filesize('queries.log') : 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Performance Monitor - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .metric-card {
            transition: transform 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
        }

        .status-good {
            color: #28a745;
        }

        .status-warning {
            color: #ffc107;
        }

        .status-danger {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-3">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Performance Monitor
                        </h5>
                        <div>
                            <a href="view_logs.php" class="btn btn-sm btn-outline-primary">View Logs</a>
                            <a href="monitor_performance.php?refresh=1" class="btn btn-sm btn-success">Refresh</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Memory Usage -->
                            <div class="col-md-3 mb-3">
                                <div class="card metric-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-memory fa-2x mb-2"></i>
                                        <h6>Memory Usage</h6>
                                        <h4 class="<?php echo $memoryUsage > 50 * 1024 * 1024 ? 'status-danger' : ($memoryUsage > 25 * 1024 * 1024 ? 'status-warning' : 'status-good'); ?>">
                                            <?php echo round($memoryUsage / 1024 / 1024, 2); ?>MB
                                        </h4>
                                        <small class="text-muted">Peak: <?php echo round($memoryPeak / 1024 / 1024, 2); ?>MB</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Database Status -->
                            <div class="col-md-3 mb-3">
                                <div class="card metric-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database fa-2x mb-2"></i>
                                        <h6>Database</h6>
                                        <h4 class="<?php echo $dbStatus === 'Connected' ? 'status-good' : 'status-danger'; ?>">
                                            <?php echo $dbStatus === 'Connected' ? 'Connected' : 'Error'; ?>
                                        </h4>
                                        <small class="text-muted">Users: <?php echo $userCount; ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- PHP Configuration -->
                            <div class="col-md-3 mb-3">
                                <div class="card metric-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cog fa-2x mb-2"></i>
                                        <h6>PHP Config</h6>
                                        <h4 class="status-good"><?php echo phpversion(); ?></h4>
                                        <small class="text-muted">Memory Limit: <?php echo $memoryLimit; ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Files -->
                            <div class="col-md-3 mb-3">
                                <div class="card metric-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                                        <h6>Log Files</h6>
                                        <h4 class="status-good"><?php echo round(($errorLogSize + $perfLogSize + $queryLogSize) / 1024, 2); ?>KB</h4>
                                        <small class="text-muted">Total Size</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Information -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">System Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>PHP Version:</strong></td>
                                                <td><?php echo phpversion(); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Memory Limit:</strong></td>
                                                <td><?php echo $memoryLimit; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Max Execution Time:</strong></td>
                                                <td><?php echo $maxExecutionTime; ?>s</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Upload Max Size:</strong></td>
                                                <td><?php echo $uploadMaxFilesize; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Post Max Size:</strong></td>
                                                <td><?php echo $postMaxSize; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Log File Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Error Log:</strong></td>
                                                <td><?php echo round($errorLogSize / 1024, 2); ?>KB</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Performance Log:</strong></td>
                                                <td><?php echo round($perfLogSize / 1024, 2); ?>KB</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Queries Log:</strong></td>
                                                <td><?php echo round($queryLogSize / 1024, 2); ?>KB</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Log Size:</strong></td>
                                                <td><?php echo round(($errorLogSize + $perfLogSize + $queryLogSize) / 1024, 2); ?>KB</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Tips -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Performance Optimization Tips</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6>Database Optimization</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success me-2"></i>Use indexes on frequently queried columns</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Limit query results with LIMIT</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Use prepared statements</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-4">
                                                <h6>PHP Optimization</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success me-2"></i>Enable OPcache if available</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Optimize image uploads</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Use efficient loops</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-4">
                                                <h6>Application Optimization</h6>
                                                <ul class="list-unstyled">
                                                    <li><i class="fas fa-check text-success me-2"></i>Minimize database queries</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Use pagination for large datasets</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Optimize image sizes</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>

</html>