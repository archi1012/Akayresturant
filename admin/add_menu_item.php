<?php
// Include admin authentication check
require_once 'check_admin.php';

// Include database configuration
require_once '../config.php';

// Set response type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get and sanitize input
$name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
$description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$category = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';

// Validate required fields
if (empty($name) || empty($category) || $price <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Name, category, and valid price are required']);
    exit;
}

// Handle image upload if present
$image_url = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit;
    }
    
    $upload_dir = '../uploads/menu/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . $_FILES['image']['name'];
    $destination = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        $image_url = 'uploads/menu/' . $file_name;
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit;
    }
}

// Insert menu item into database
$query = "INSERT INTO menu_items (name, description, price, category, image_url) 
          VALUES ('$name', '$description', $price, '$category', '$image_url')";

if ($conn->query($query) === TRUE) {
    $item_id = $conn->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Menu item added successfully',
        'item' => [
            'id' => $item_id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category' => $category,
            'image' => $image_url
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to add menu item: ' . $conn->error]);
}
?>