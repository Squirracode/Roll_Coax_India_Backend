<?php
// Replace with your actual database credentials
$host = 'localhost';
$dbName = 'dashboard';
$dbUname = 'rcipl_admin';
$dbPass = '9033734886@Nik';

// Function to authenticate user
function authenticateUser() {
    $validUsername = 'ricpl_admin'; // Replace with your valid username
    $validPassword = 'Welcome@123'; // Replace with your valid password

    $providedUsername = $_SERVER['PHP_AUTH_USER'] ?? '';
    $providedPassword = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    return ($providedUsername === $validUsername && $providedPassword === $validPassword);

    //return true;
}

// Function to insert an  data in and QRdetails table
function insertQRData($qrData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Set up the PDO connection
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $isDeleted = 0;
        // Prepare the SQL query to insert QR data
        $stmt = $conn->prepare("INSERT INTO qrDetails (clientId, qrUniqueKey, departmentName, serialNumber,contractorName, swl, swp, certificateIssueDate, certificateExpDate, certificateLink, created_at, modified_at, isDeleted) VALUES (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?)");

        
        $dateTimeissueDate = new DateTime($qrData['certificateIssueDate']);
        $dateTimeissueDate->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $issueDate = $dateTimeissueDate->format('Y-m-d H:i:s'); 
        
        
        $dateTimeexpDate = new DateTime($qrData['certificateExpDate']);
        $dateTimeexpDate->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $expDate = $dateTimeissueDate->format('Y-m-d H:i:s'); 
        
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $current = $dateTime->format('Y-m-d H:i:s'); 

        // Execute the statement with the provided data
        $stmt->execute([
            $qrData['clientId'],
            $qrData['qrUniqueKey'],
            $qrData['departmentName'],
            $qrData['serialNumber'],
            $qrData['contractorName'],
            $qrData['swl'],
            $qrData['swp'],
            $issueDate,
            $expDate,
            $qrData['certificateLink'],
            $current,
            $current,
            $isDeleted
        ]);

        // Return the ID of the inserted QR record
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        error_log($e->getMessage()); // Log the error for debugging purposes
        return null; // Return null if insertion fails
    }
}



// Function to fetch all QR details with client information
function fetchAllQr() {
     global $host, $dbName, $dbUname, $dbPass;
   
    try {
        // Set up the PDO connection
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     
        // Prepare the SQL query to fetch all QR details and join with client details
        $stmt = $conn->prepare("
            SELECT 
                qrDetails.*, 
                clients.clientName, 
                clients.Address as clientAddress
            FROM 
                qrDetails
            JOIN 
                clients ON qrDetails.clientId = clients.clientId
                WHERE qrDetails.isDeleted != 1
        ");
        $stmt->execute();
        

        // Fetch all results as an associative array
        $qrDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $qrDetails;
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        error_log($e->getMessage()); // Log the error for debugging purposes
        return null; // Return null if insertion fails
    }
}

function fetchAllQrNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Set up the PDO connection
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get parameters from request with defaults
        // Get parameters from request
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $offset = ($page - 1) * $pageSize;

        // Base query to fetch QR details with client info
        $query = "
            SELECT 
                qrDetails.*, 
                clients.clientName, 
                clients.Address as clientAddress
            FROM 
                qrDetails
            JOIN 
                clients ON qrDetails.clientId = clients.clientId
            WHERE 
                qrDetails.isDeleted != 1
        ";

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (clients.clientName LIKE :search OR qrDetails.departmentName LIKE :search OR qrDetails.qrUniqueKey LIKE :search OR qrDetails.serialNumber LIKE :search OR qrDetails.swl LIKE :search OR qrDetails.swp LIKE :search)";
        }

        // Add pagination
        $query .= " LIMIT :offset, :pageSize";

        // Prepare and bind parameters
        $stmt = $conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $qrDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countQuery = "
            SELECT COUNT(*) AS total 
            FROM qrDetails
            JOIN clients ON qrDetails.clientId = clients.clientId
            WHERE qrDetails.isDeleted != 1
        ";

        if (!empty($search)) {
            $countQuery .= " AND (clients.clientName LIKE :search OR qrDetails.departmentName LIKE :search OR qrDetails.qrUniqueKey LIKE :search OR qrDetails.serialNumber LIKE :search OR qrDetails.swl LIKE :search OR qrDetails.swp LIKE :search)";
        }

        $countStmt = $conn->prepare($countQuery);

        if (!empty($search)) {
            $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Return structured response
        return [
            'data' => $qrDetails,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];

    } catch (PDOException $e) {
        // Log error and return structured error response
        error_log($e->getMessage());
        return [
            'error' => 'Database error occurred',
            'message' => $e->getMessage()
        ];
    }
}

// Function to fetch all QR details by ID with client information
function fetchAllQrByID($qrId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Set up the PDO connection
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL query to fetch QR details by unique ID and join with client details
        $stmt = $conn->prepare("
            SELECT 
                qrDetails.*, 
                clients.clientName, 
                clients.Address as clientAddress
            FROM 
                qrDetails
            JOIN 
                clients ON qrDetails.clientId = clients.clientId
            WHERE 
                qrDetails.qrId = :qrId;
        ");

        // Bind the parameter to the query
        $stmt->bindParam(':qrId', $qrId, PDO::PARAM_STR);

        // Execute the query
        $stmt->execute();

        // Fetch the result as an associative array
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the result
        return $result;

    } catch (PDOException $e) {
        // Handle any errors
        echo "Error: " . $e->getMessage();
        return false;
    }
}

function deleteQRData($qrid) {
    global $host, $dbName, $dbUname, $dbPass;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      
        // Prepare SQL statement to delete the inquiry
        $stmt = $conn->prepare("UPDATE qrDetails SET isDeleted = 1 WHERE qrid = ?;");
        $stmt->execute([$qrid]);
        
        // Check if any rows were affected (if deletion was successful)
        if ($stmt->rowCount() > 0) {
            return true; // Deletion successful
        } else {
            return false; // Inquiry with given ID not found or not deleted
        }
    } catch (PDOException $e) {
        // Handle database connection or query execution errors
        error_log("Error deleting inquiry: " . $e->getMessage());
        return false; // Return false if deletion fails
    }
}

function UpdateQRData($qrid,$qrData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $current = $dateTime->format('Y-m-d H:i:s'); 
        
        // Prepare SQL statement to update the qr details
        $stmt = $conn->prepare("UPDATE qrDetails SET qrUniqueKey= ?,
        clientId=?,
        departmentName= ?,
        serialNumber= ?,
        contractorName=?,
        swl= ?,
        swp= ?,
        certificateIssueDate= ?,
        certificateExpDate= ?,
        certificateLink= ?, modified_at= ? WHERE qrid =?");
        $stmt->execute([$qrData['qrUniqueKey'],
        $qrData['clientId'],
        $qrData['departmentName'],
        $qrData['serialNumber'],
        $qrData['contractorName'],
        $qrData['swl'],
        $qrData['swp'],
        $qrData['certificateIssueDate'],
        $qrData['certificateExpDate'],
        $qrData['certificateLink'],
        $current,
        $qrid]);
    
        return true; // Return true on successful update
    }
     catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}



?>
