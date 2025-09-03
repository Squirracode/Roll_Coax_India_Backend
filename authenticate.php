<?php

// Replace with your actual database credentials
$host = 'localhost';
$dbName = 'dashboard';
$dbUname = 'rcipl_admin';
$dbPass = '9033734886@Nik';

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}
// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';

// Check if the endpoint is for fetching user details
if ($endpoint === 'checkLoginDetails') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, fetch user details
        $requestData = json_decode(file_get_contents('php://input'), true);

        if (isset($requestData['username']) && isset($requestData['password'])) {
            $username = $requestData['username'];
            $password = $requestData['password'];
            
            // Connect to the database
            $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare and execute the SELECT query to fetch user details
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :password");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
            $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userDetails) {
                // Return JSON response with user details
                header('Content-Type: application/json');
                echo json_encode($userDetails);
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Invalid username or password"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Username and password are required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} 

// Function to authenticate user
function authenticateUser() {
    $validUsername = 'ricpl_admin'; // Replace with your valid username
    $validPassword = 'Welcome@123'; // Replace with your valid password

    $providedUsername = $_SERVER['PHP_AUTH_USER'] ?? '';
    $providedPassword = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    return ($providedUsername === $validUsername && $providedPassword === $validPassword);

    //return true;
}

?>
