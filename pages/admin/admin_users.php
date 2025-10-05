<?php
// Start output buffering to prevent headers already sent errors
ob_start();

include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?page=login');
}

// Include header
include 'includes/header.php';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_status'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = sanitizeInput($_POST['action'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && in_array($action, ['verify', 'suspend', 'activate'])) {
        try {
            if ($action === 'verify') {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                setFlashMessage('success', 'User verified successfully.');
            } elseif ($action === 'suspend') {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                setFlashMessage('success', 'User suspended successfully.');
            } elseif ($action === 'activate') {
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                setFlashMessage('success', 'User activated successfully.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update user status.');
            error_log("User status update error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_users');
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        try {
            // Don't allow admin to delete themselves
            if ($userId === $_SESSION['user_id']) {
                setFlashMessage('error', 'You cannot delete your own account.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                setFlashMessage('success', 'User deleted successfully.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to delete user.');
            error_log("User deletion error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_users');
}

// Get filter parameters
$filter = sanitizeInput($_GET['filter'] ?? 'all');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as property_count,
        (SELECT COUNT(*) FROM property_visits WHERE tenant_id = u.id) as visit_count,
        (SELECT COUNT(*) FROM rental_agreements WHERE tenant_id = u.id OR owner_id = u.id) as rental_count
        FROM users u 
        WHERE 1=1";

$params = [];

if ($filter === 'tenants') {
    $sql .= " AND u.user_type = 'tenant'";
} elseif ($filter === 'owners') {
    $sql .= " AND u.user_type = 'owner'";
} elseif ($filter === 'admins') {
    $sql .= " AND u.user_type = 'admin'";
} elseif ($filter === 'verified') {
    $sql .= " AND u.is_verified = 1";
} elseif ($filter === 'unverified') {
    $sql .= " AND u.is_verified = 0";
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY u.created_at DESC";

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 15;
$offset = ($page - 1) * $recordsPerPage;

// Get total count
$countSql = str_replace("SELECT u.*, (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as property_count, (SELECT COUNT(*) FROM property_visits WHERE tenant_id = u.id) as visit_count, (SELECT COUNT(*) FROM rental_agreements WHERE tenant_id = u.id OR owner_id = u.id) as rental_count", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Add pagination
$sql .= " LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $recordsPerPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pagination = paginate($totalRecords, $recordsPerPage, $page);

// Get user statistics
$stats = [
    'total_users' => count($users),
    'tenants' => count(array_filter($users, function ($u) {
        return $u['user_type'] === 'tenant';
    })),
    'owners' => count(array_filter($users, function ($u) {
        return $u['user_type'] === 'owner';
    })),
    'verified' => count(array_filter($users, function ($u) {
        return $u['is_verified'];
    }))
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5" style="margin-top: 76px;">
        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6 fw-bold text-primary">User Management</h1>
                <p class="text-muted">Manage customers, property owners, and system users</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $stats['total_users']; ?></div>
                    <div class="dashboard-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo $stats['tenants']; ?></div>
                    <div class="dashboard-label">Tenants</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo $stats['owners']; ?></div>
                    <div class="dashboard-label">Property Owners</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo $stats['verified']; ?></div>
                    <div class="dashboard-label">Verified Users</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Type</label>
                        <select class="form-select" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="tenants" <?php echo $filter === 'tenants' ? 'selected' : ''; ?>>Tenants Only</option>
                            <option value="owners" <?php echo $filter === 'owners' ? 'selected' : ''; ?>>Property Owners Only</option>
                            <option value="admins" <?php echo $filter === 'admins' ? 'selected' : ''; ?>>Admins Only</option>
                            <option value="verified" <?php echo $filter === 'verified' ? 'selected' : ''; ?>>Verified Users</option>
                            <option value="unverified" <?php echo $filter === 'unverified' ? 'selected' : ''; ?>>Unverified Users</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by name, email, or phone...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Contact</th>
                                <th>Activity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No users found</h6>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <small class="text-muted">
                                                        Joined <?php echo formatDate($user['created_at']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'owner' ? 'success' : 'info'); ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?php echo htmlspecialchars($user['phone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>Properties: <?php echo $user['property_count']; ?></div>
                                                <div>Visits: <?php echo $user['visit_count']; ?></div>
                                                <div>Rentals: <?php echo $user['rental_count']; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['is_verified']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if (!$user['is_verified']): ?>
                                                    <button class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#verifyModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#suspendModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Verify Modal -->
                                    <div class="modal fade" id="verifyModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Verify User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">

                                                        <p>Are you sure you want to verify this user?</p>
                                                        <div class="alert alert-info">
                                                            <strong>User:</strong> <?php echo htmlspecialchars($user['name']); ?><br>
                                                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                                            <strong>Type:</strong> <?php echo ucfirst($user['user_type']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_user_status" class="btn btn-success">
                                                            <i class="fas fa-check me-2"></i>Verify User
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Suspend Modal -->
                                    <div class="modal fade" id="suspendModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Suspend User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="suspend">

                                                        <p>Are you sure you want to suspend this user?</p>
                                                        <div class="alert alert-warning">
                                                            <strong>User:</strong> <?php echo htmlspecialchars($user['name']); ?><br>
                                                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                                            <strong>Type:</strong> <?php echo ucfirst($user['user_type']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_user_status" class="btn btn-warning">
                                                            <i class="fas fa-pause me-2"></i>Suspend User
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Delete User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                                                        <p>Are you sure you want to permanently delete this user?</p>
                                                        <div class="alert alert-danger">
                                                            <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently deleted.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger">
                                                            <i class="fas fa-trash me-2"></i>Delete User
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-4">
                <?php echo generatePagination($pagination, 'admin_users.php'); ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>