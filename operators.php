<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/functionsOperators.php';

// Allow CORS from any origin unconditionally
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origin}");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$endpoint = $_GET['endpoint'] ?? '';
$authenticated = authenticateUser();

if (!$authenticated) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
    exit();
}

header('Content-Type: application/json');

if ($endpoint === 'getOperators') {
    $operators = getAllOperators();
    if ($operators !== false) {
        echo json_encode(array("status" => "success", "data" => $operators));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to retrieve operators"));
    }

} elseif ($endpoint === 'addOperator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? ($_POST['name'] ?? ''));
    $email = trim($data['email'] ?? ($_POST['email'] ?? ''));

    if (empty($email)) {
        $email = null;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Operator name is required"));
        exit();
    }

    $insertedId = addOperator($name, $email);
    if ($insertedId) {
        echo json_encode(array("status" => "success", "message" => "Operator added successfully", "id" => $insertedId));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to add operator"));
    }

} elseif ($endpoint === 'updateOperator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? ($_POST['id'] ?? 0));
    $name = trim($data['name'] ?? ($_POST['name'] ?? ''));
    $email = trim($data['email'] ?? ($_POST['email'] ?? ''));

    if (empty($email)) {
        $email = null;
    }

    if (!$id || empty($name)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Operator ID and Name are required"));
        exit();
    }

    $updated = updateOperator($id, $name, $email);
    if ($updated) {
        echo json_encode(array("status" => "success", "message" => "Operator updated successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to update operator"));
    }

} elseif ($endpoint === 'deleteOperator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($_GET['id'] ?? ($data['id'] ?? ($_POST['id'] ?? 0)));

    if (!$id) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Operator ID is required"));
        exit();
    }

    $deleted = deleteOperator($id);
    if ($deleted) {
        echo json_encode(array("status" => "success", "message" => "Operator deleted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Failed to delete operator"));
    }

} else {
    http_response_code(404);
    echo json_encode(array("status" => "error", "message" => "Invalid endpoint"));
}
