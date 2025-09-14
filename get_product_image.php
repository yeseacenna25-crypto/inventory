<?php
$mysqli = new mysqli("localhost", "root", "", "inventory_negrita");
if ($mysqli->connect_error) {
    http_response_code(500);
    exit('Database connection failed');
}
$id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid product ID');
}
$result = $mysqli->query("SELECT product_image FROM products WHERE product_id = $id");
if ($row = $result->fetch_assoc()) {
    $img = $row['product_image'];
    // If image is a file path (ends with .jpg, .jpeg, .png, .gif, etc.)
    if (is_string($img) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $img)) {
        $path = 'uploads/' . $img;
        if (file_exists($path)) {
            $mime = mime_content_type($path);
            header("Content-Type: $mime");
            readfile($path);
            exit();
        }
    }
    // If image is a BLOB (binary string, not a filename)
    if (is_string($img) && !preg_match('/\.(jpg|jpeg|png|gif)$/i', $img) && !empty($img)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $img);
        finfo_close($finfo);
        header("Content-Type: $mime");
        echo $img;
        exit();
    }
}
http_response_code(404);
echo 'Image not found';
?>
