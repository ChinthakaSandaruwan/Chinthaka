<?php

/**
 * Log Viewer - View application logs
 * Access: http://localhost/chinthaka/logs/view_logs.php
 */

// Simple authentication (you can enhance this)
$logPassword = 'admin123'; // Change this password
$authenticated = false;

if (isset($_POST['password']) && $_POST['password'] === $logPassword) {
    $authenticated = true;
    setcookie('log_auth', '1', time() + 3600); // 1 hour
} elseif (isset($_COOKIE['log_auth']) && $_COOKIE['log_auth'] === '1') {
    $authenticated = true;
}

if (!$authenticated) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Log Viewer - Authentication</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Log Viewer Authentication</h5>
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

// Get log type
$logType = $_GET['type'] ?? 'error';
$logFile = '';

switch ($logType) {
    case 'error':
        $logFile = 'error.log';
        break;
    case 'performance':
        $logFile = 'performance.log';
        break;
    case 'queries':
        $logFile = 'queries.log';
        break;
    default:
        $logType = 'error';
        $logFile = 'error.log';
}

// Read log file
$logContent = '';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    // Show last 100 lines
    $lines = explode("\n", $logContent);
    $logContent = implode("\n", array_slice($lines, -100));
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Log Viewer - <?php echo ucfirst($logType); ?> Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-content {
            background-color: #1e1e1e;
            color: #ffffff;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .log-nav {
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-3">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">RentFinder SL - Log Viewer</h5>
                        <div>
                            <a href="?type=error" class="btn btn-sm <?php echo $logType === 'error' ? 'btn-primary' : 'btn-outline-primary'; ?>">Error Log</a>
                            <a href="?type=performance" class="btn btn-sm <?php echo $logType === 'performance' ? 'btn-primary' : 'btn-outline-primary'; ?>">Performance</a>
                            <a href="?type=queries" class="btn btn-sm <?php echo $logType === 'performance' ? 'btn-primary' : 'btn-outline-primary'; ?>">Queries</a>
                            <a href="view_logs.php?type=<?php echo $logType; ?>&refresh=1" class="btn btn-sm btn-success">Refresh</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6><?php echo ucfirst($logType); ?> Log (Last 100 entries)</h6>
                        <div class="log-content"><?php echo htmlspecialchars($logContent); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>

</html>