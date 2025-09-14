<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log("fetch_product_images.php called");

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Get POST data
$input = file_get_contents('php://input');
error_log("Received input: " . $input);

$data = json_decode($input, true);

if (!isset($data['productIds']) || !is_array($data['productIds'])) {
    error_log("Invalid product IDs received");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product IDs']);
    exit;
}

$productIds = array_map('intval', $data['productIds']); // Sanitize IDs
error_log("Processing product IDs: " . implode(', ', $productIds));

$response = [];

if (empty($productIds)) {
    echo json_encode($response);
    exit;
}

// Create placeholders for prepared statement
$placeholders = str_repeat('?,', count($productIds) - 1) . '?';
$sql = "SELECT product_id, product_image FROM products WHERE product_id IN ($placeholders)";
error_log("SQL Query: " . $sql);

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("Failed to prepare statement: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit;
}

// Bind parameters
$types = str_repeat('i', count($productIds));
mysqli_stmt_bind_param($stmt, $types, ...$productIds);

// Execute query
if (!mysqli_stmt_execute($stmt)) {
    error_log("Query execution failed: " . mysqli_stmt_error($stmt));
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed']);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$foundProducts = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $productId = $row['product_id'];
    $imageData = $row['product_image'];
    $foundProducts++;
    
    error_log("Processing product $productId, image data length: " . strlen($imageData));
    
    if (!empty($imageData)) {
        // Check if it's a filename or binary data
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageData)) {
            // It's a filename
            error_log("Product $productId has filename: " . $imageData);
            $response[$productId] = $imageData;
        } else {
            // It's binary data - convert to base64
            try {
                // Check if it's valid image data by looking at the first few bytes
                if (strlen($imageData) > 8) {
                    $header = substr($imageData, 0, 8);
                    
                    // JPEG detection
                    if (substr($imageData, 0, 2) === "\xFF\xD8") {
                        error_log("Product $productId detected as JPEG binary data");
                        $response[$productId] = "data:image/jpeg;base64," . base64_encode($imageData);
                    }
                    // PNG detection
                    elseif ($header === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
                        error_log("Product $productId detected as PNG binary data");
                        $response[$productId] = "data:image/png;base64," . base64_encode($imageData);
                    }
                    // GIF detection
                    elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
                        error_log("Product $productId detected as GIF binary data");
                        $response[$productId] = "data:image/gif;base64," . base64_encode($imageData);
                    }
                    // Try using finfo as backup
                    else {
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            if ($finfo) {
                                $mimeType = finfo_buffer($finfo, $imageData);
                                finfo_close($finfo);
                                if ($mimeType && strpos($mimeType, 'image/') === 0) {
                                    error_log("Product $productId detected via finfo as: " . $mimeType);
                                    $response[$productId] = "data:$mimeType;base64," . base64_encode($imageData);
                                } else {
                                    error_log("Product $productId - finfo returned non-image type: " . $mimeType);
                                }
                            }
                        } else {
                            error_log("Product $productId - finfo not available, treating as JPEG");
                            $response[$productId] = "data:image/jpeg;base64," . base64_encode($imageData);
                        }
                    }
                } else {
                    error_log("Product $productId has image data too short: " . strlen($imageData) . " bytes");
                }
            } catch (Exception $e) {
                error_log("Error processing image for product $productId: " . $e->getMessage());
            }
        }
    } else {
        error_log("Product $productId has no image data");
    }
}

error_log("Found $foundProducts products, returning response with " . count($response) . " images");
error_log("Response: " . json_encode($response, JSON_PRETTY_PRINT));

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($response);
?>
