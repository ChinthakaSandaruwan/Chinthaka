<?php
// Database setup script for RentFinder SL
// Run this script once to create the database and tables

$host = 'localhost';
$dbname = 'rentfinder_sl';
$username = 'root';
$password = '123321555';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    echo "<h2>Database Setup for RentFinder SL</h2>";
    echo "<p>Creating database: <strong>$dbname</strong></p>";

    // Create tables
    $tables = [
        "users" => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                phone VARCHAR(15) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                user_type ENUM('tenant', 'owner', 'admin') DEFAULT 'tenant',
                is_verified BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "otp_verification" => "
            CREATE TABLE IF NOT EXISTS otp_verification (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(15) NOT NULL,
                otp_code VARCHAR(6) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                is_used BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "properties" => "
            CREATE TABLE IF NOT EXISTS properties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                property_type ENUM('apartment', 'house', 'villa', 'room') NOT NULL,
                location VARCHAR(100) NOT NULL,
                address TEXT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                bedrooms INT DEFAULT 0,
                bathrooms INT DEFAULT 0,
                area_sqft INT DEFAULT 0,
                amenities TEXT,
                images JSON,
                is_available BOOLEAN DEFAULT TRUE,
                is_verified BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "property_visits" => "
            CREATE TABLE IF NOT EXISTS property_visits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                tenant_id INT NOT NULL,
                visit_date DATE NOT NULL,
                visit_time TIME NOT NULL,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "rental_agreements" => "
            CREATE TABLE IF NOT EXISTS rental_agreements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                tenant_id INT NOT NULL,
                owner_id INT NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                monthly_rent DECIMAL(10,2) NOT NULL,
                security_deposit DECIMAL(10,2) DEFAULT 0,
                status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "payments" => "
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rental_agreement_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_type ENUM('rent', 'deposit', 'maintenance') NOT NULL,
                payment_method ENUM('card', 'bank_transfer') NOT NULL,
                transaction_id VARCHAR(100),
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                payment_date TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (rental_agreement_id) REFERENCES rental_agreements(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",

        "payment_cards" => "
            CREATE TABLE IF NOT EXISTS payment_cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                card_token VARCHAR(255) NOT NULL,
                last_four_digits VARCHAR(4) NOT NULL,
                card_type VARCHAR(20) NOT NULL,
                expiry_month INT NOT NULL,
                expiry_year INT NOT NULL,
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];

    // Execute table creation
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
        echo "<p>✓ Table <strong>$tableName</strong> created successfully</p>";
    }

    // Insert sample data
    echo "<h3>Inserting Sample Data</h3>";

    // Create admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password, user_type, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@rentfinder.lk', '0712345678', $adminPassword, 'admin', true]);
    echo "<p>✓ Admin user created (email: admin@rentfinder.lk, password: admin123)</p>";

    // Set default commission rate
    $_SESSION['commission_rate'] = 5;

    // Create sample property owner
    $ownerPassword = password_hash('owner123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password, user_type, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['John Silva', 'john@example.com', '0712345679', $ownerPassword, 'owner', true]);

    // Get owner ID (either from insert or existing record)
    if ($pdo->lastInsertId() == 0) {
        // User already exists, get their ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['john@example.com']);
        $ownerId = $stmt->fetchColumn();
    } else {
        $ownerId = $pdo->lastInsertId();
    }
    echo "<p>✓ Sample property owner created</p>";

    // Create sample tenant
    $tenantPassword = password_hash('tenant123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password, user_type, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Sarah Fernando', 'sarah@example.com', '0712345680', $tenantPassword, 'tenant', true]);
    echo "<p>✓ Sample tenant created</p>";

    // Create sample properties
    $sampleProperties = [
        [
            'owner_id' => $ownerId,
            'title' => 'Modern 2BR Apartment in Colombo 7',
            'description' => 'Beautiful modern apartment in the heart of Colombo 7. Fully furnished with all amenities. Perfect for professionals working in the city.',
            'property_type' => 'apartment',
            'location' => 'colombo',
            'address' => '123/5, Galle Road, Colombo 07',
            'price' => 75000.00,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'area_sqft' => 1200,
            'amenities' => json_encode(['Air Conditioning', 'Parking', 'Security', 'Gym', 'Swimming Pool']),
            'images' => json_encode(['assets/images/placeholder.jpg']),
            'is_verified' => true
        ],
        [
            'owner_id' => $ownerId,
            'title' => 'Spacious 3BR House in Kandy',
            'description' => 'Charming house with garden in peaceful Kandy. Ideal for families. Close to schools and hospitals.',
            'property_type' => 'house',
            'location' => 'kandy',
            'address' => '456, Peradeniya Road, Kandy',
            'price' => 45000.00,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area_sqft' => 1800,
            'amenities' => json_encode(['Garden', 'Parking', 'Security', 'Water Tank']),
            'images' => json_encode(['assets/images/placeholder.jpg']),
            'is_verified' => true
        ],
        [
            'owner_id' => $ownerId,
            'title' => 'Luxury Villa in Galle',
            'description' => 'Stunning beachfront villa with ocean views. Perfect for vacation rental or permanent residence.',
            'property_type' => 'villa',
            'location' => 'galle',
            'address' => '789, Beach Road, Galle',
            'price' => 120000.00,
            'bedrooms' => 4,
            'bathrooms' => 3,
            'area_sqft' => 2500,
            'amenities' => json_encode(['Ocean View', 'Private Beach Access', 'Pool', 'Garden', 'Security']),
            'images' => json_encode(['assets/images/placeholder.jpg']),
            'is_verified' => true
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO properties (owner_id, title, description, property_type, location, address, price, bedrooms, bathrooms, area_sqft, amenities, images, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($sampleProperties as $property) {
        $stmt->execute([
            $property['owner_id'],
            $property['title'],
            $property['description'],
            $property['property_type'],
            $property['location'],
            $property['address'],
            $property['price'],
            $property['bedrooms'],
            $property['bathrooms'],
            $property['area_sqft'],
            $property['amenities'],
            $property['images'],
            $property['is_verified']
        ]);
    }
    echo "<p>✓ Sample properties created</p>";

    echo "<h3>Database Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Test Accounts Created:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@rentfinder.lk / admin123</li>";
    echo "<li><strong>Property Owner:</strong> john@example.com / owner123</li>";
    echo "<li><strong>Tenant:</strong> sarah@example.com / tenant123</li>";
    echo "</ul>";
    echo "</div>";

    echo "<p><a href='index.php' class='btn btn-primary'>Go to RentFinder SL</a></p>";
} catch (PDOException $e) {
    echo "<h2>Database Setup Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
