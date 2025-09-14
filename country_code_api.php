<?php
// Simple country code API (static list for demo)
header('Content-Type: application/json');

$country_codes = [
    ['country' => 'Philippines', 'code' => '+63'],
    ['country' => 'United States', 'code' => '+1'],
    ['country' => 'United Kingdom', 'code' => '+44'],
    ['country' => 'India', 'code' => '+91'],
    ['country' => 'Canada', 'code' => '+1'],
    ['country' => 'Australia', 'code' => '+61'],
    // Add more countries as needed
];

if (isset($_GET['country'])) {
    $search = strtolower(trim($_GET['country']));
    $result = array_filter($country_codes, function($item) use ($search) {
        return strpos(strtolower($item['country']), $search) !== false;
    });
    echo json_encode(array_values($result));
} else {
    echo json_encode($country_codes);
}
?>
