<?php
// JSON API for property search filters
// Returns paginated, filtered properties

// Bootstrap app
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';

header('Content-Type: application/json');

try {
    // Only allow GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    // Collect and sanitize filters
    $filters = [
        'location'   => sanitizeInput($_GET['location'] ?? ''),
        'type'       => sanitizeInput($_GET['type'] ?? ''),
        'min_price'  => sanitizeInput($_GET['min_price'] ?? ''),
        'max_price'  => sanitizeInput($_GET['max_price'] ?? ''),
        'bedrooms'   => sanitizeInput($_GET['bedrooms'] ?? ''),
        'search'     => sanitizeInput($_GET['search'] ?? ''),
    ];

    // Pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 12)));
    $offset = ($page - 1) * $perPage;

    // Build base query (mirror pages/properties/properties.php)
    $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.is_available = 1 AND p.is_verified = 1";

    $params = [];

    if (!empty($filters['location'])) {
        $sql .= " AND p.location = :location";
        $params['location'] = $filters['location'];
    }

    if (!empty($filters['type'])) {
        $sql .= " AND p.property_type = :type";
        $params['type'] = $filters['type'];
    }

    if (!empty($filters['min_price'])) {
        $sql .= " AND p.price >= :min_price";
        $params['min_price'] = (int)$filters['min_price'];
    }

    if (!empty($filters['max_price'])) {
        $sql .= " AND p.price <= :max_price";
        $params['max_price'] = (int)$filters['max_price'];
    }

    if (!empty($filters['bedrooms'])) {
        $sql .= " AND p.bedrooms >= :bedrooms";
        $params['bedrooms'] = (int)$filters['bedrooms'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (p.title LIKE :search OR p.description LIKE :search OR p.location LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }

    // Count query
    $countSql = str_replace(
        "SELECT p.*, u.name as owner_name, u.phone as owner_phone",
        "SELECT COUNT(*)",
        $sql
    );

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query with pagination
    $sql .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";
    $params['offset'] = $offset;
    $params['limit'] = $perPage;

    $stmt = $pdo->prepare($sql);
    // Bind numeric params explicitly for LIMIT/OFFSET (MySQL requires integers)
    foreach (['offset', 'limit'] as $key) {
        if (isset($params[$key])) {
            $stmt->bindValue(':' . $key, (int)$params[$key], PDO::PARAM_INT);
            unset($params[$key]);
        }
    }
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize image field to first image URL (like UI uses)
    foreach ($rows as &$row) {
        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded[0])) {
                $row['primary_image'] = $decoded[0];
            } else {
                $row['primary_image'] = 'public/images/placeholder.jpg';
            }
        } else {
            $row['primary_image'] = 'public/images/placeholder.jpg';
        }
    }

    $response = [
        'meta' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ],
        'filters' => $filters,
        'data' => $rows,
    ];

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Search API error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal Server Error']);
}
