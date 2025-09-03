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


// Function to insert an item into the quotationitem table
function insertAssignableItem($assignableId, $item,$sacCode, $uom, $qty, $unit_price) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO assignableItems (assignableId, itemDetails, sacCode, uom, qty, unitRate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$assignableId, $item,$sacCode, $uom, $qty, $unit_price]);
        
        return true; // Return true on successful insertion
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}

function fetchAssignableDetails($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch quotation details
        $stmt = $conn->prepare("SELECT a.*, q.qutReference FROM assignables a JOIN quotation q ON a.qutId = q.qutId WHERE a.assignableId = ?");
        $stmt->execute([$assignableId]);
        $assignable = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch items associated with the quotation
        $stmt = $conn->prepare("SELECT * FROM assignableItems WHERE assignableId = ?");
        $stmt->execute([$assignableId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Construct the quotation object
        $assignableObject = array(
            'assignable' => $assignable,
            'items' => $items
        );

        return $assignableObject; // Return quotation details along with items
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

// Function to insert assignable data into the database
function insertAssignable($assignableData) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if($assignableData['poDate']){
            $dateTime = new DateTime($assignableData['poDate']);
            // Set the desired time zone for storing in the database (IST)
            $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $poDate = $dateTime->format('Y-m-d H:i:s');
        }else{
            $assignableData['poDate'] = null;
        }
        
        $cdateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        
        // Prepare and execute the SQL query to insert assignable data
        $stmt = $conn->prepare("INSERT INTO assignables (qutId, poDate, status, subject, clientId, contactName, created_by, scopeOfWork, termAndCondition, poNumber, poFilePath, poRequired, poPending, created_at, last_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$assignableData['qutId'], $poDate, $assignableData['status'], $assignableData['subject'], $assignableData['clientId'], $assignableData['contactName'], $assignableData['created_by'], $assignableData['scopeOfWork'], $assignableData['termAndCondition'], $assignableData['poNumber'], $assignableData['poFilePath'], $assignableData['poRequired'], $assignableData['poPending'], $currentDate, $currentDate]);
        
        // Return the ID of the inserted assignable
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        echo $e;
        return null; // Return null if insertion fails
    }
}

// Function to fetch all assignables
function fetchAllAssignables() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("SELECT a.*, q.qutReference, c.clientName, tc.teamName FROM assignables a JOIN quotation q ON a.qutId = q.qutId JOIN clients c ON a.clientId = c.clientId LEFT JOIN ( SELECT ua.assignableId, ua.teamId, ua.assignDate FROM userAssignments ua JOIN ( SELECT assignableId, MAX(assignDate) AS maxAssignDate FROM userAssignments GROUP BY assignableId ) latest ON ua.assignableId = latest.assignableId AND ua.assignDate = latest.maxAssignDate ) latestAssignments ON a.assignableId = latestAssignments.assignableId LEFT JOIN teamConfiguration tc ON latestAssignments.teamId = tc.teamId");
        $assignables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($assignables as &$assignable) {
            if ($assignable['poRequired']) {
                if ($assignable['poPending']) {
                    $assignable['poNumber'] = 'Pending';
                    $assignable['poDate'] = 'Pending';
                }
            } else {
                $assignable['poNumber'] = 'Not Required';
                $assignable['poDate'] = 'Not Required';
            }
            if ($assignable['teamName'] == '') {
                $assignable['teamName'] = 'Not Assigned';
            }
        }
        return $assignables; // Return all assignables
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function fetchAllAssignablesNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get request parameters from POST
        $data = json_decode(file_get_contents("php://input"), true) ?: [];

        // Extract filters from the nested structure
        $filters = isset($data['filters']) ? $data['filters'] : [];
        $searchTerm = isset($data['searchTerm']) ? trim($data['searchTerm']) : '';
        $statuses = isset($data['statuses']) ? (array)$data['statuses'] : [];
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? (int)$data['pageSize'] : 10;
        $offset = ($page - 1) * $pageSize;


        // Base query
        $query = "SELECT a.*, q.qutReference, c.clientName, tc.teamName 
                  FROM assignables a 
                  JOIN quotation q ON a.qutId = q.qutId 
                  JOIN clients c ON a.clientId = c.clientId 
                  LEFT JOIN (
                      SELECT ua.assignableId, ua.teamId, ua.assignDate 
                      FROM userAssignments ua 
                      JOIN (
                          SELECT assignableId, MAX(assignDate) AS maxAssignDate 
                          FROM userAssignments 
                          GROUP BY assignableId
                      ) latest ON ua.assignableId = latest.assignableId AND ua.assignDate = latest.maxAssignDate
                  ) latestAssignments ON a.assignableId = latestAssignments.assignableId 
                  LEFT JOIN teamConfiguration tc ON latestAssignments.teamId = tc.teamId 
                  WHERE 1=1";

        // Add search filter
        if (!empty($searchTerm)) {
            $query .= " AND (c.clientName LIKE :search OR q.qutReference LIKE :search OR a.poNumber LIKE :search OR a.subject LIKE :search OR a.scopeOfWork LIKE :search OR a.created_by LIKE :search OR a.status LIKE :search)";
        }
        
        if (strtolower($searchTerm) === 'pending') {
    $query .= " OR (a.poRequired = 1 AND a.poPending = 1)";
}

        // Add status filter dynamically
        $statusPlaceholders = [];
        if (!empty($statuses)) {
            foreach ($statuses as $index => $status) {
                $statusPlaceholders[] = ":status$index";
            }
            $query .= " AND a.status IN (" . implode(',', $statusPlaceholders) . ")";
        }

        // Add pagination
        $query .= " LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);

        // Bind parameters
        if (!empty($searchTerm)) {
            $searchParam = "%$searchTerm%";
            $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
        }
        if (!empty($statuses)) {
            foreach ($statuses as $index => $status) {
                $stmt->bindValue(":status$index", $status, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $assignables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format response
        foreach ($assignables as &$assignable) {
            if ($assignable['poRequired']) {
                if ($assignable['poPending']) {
                    $assignable['poNumber'] = 'Pending';
                    $assignable['poDate'] = 'Pending';
                }
            } else {
                $assignable['poNumber'] = 'Not Required';
                $assignable['poDate'] = 'Not Required';
            }
            if (empty($assignable['teamName'])) {
                $assignable['teamName'] = 'Not Assigned';
            }
        }

        // Get filtered count (with search and status filters)
        $filteredCountQuery = "SELECT COUNT(*) AS filteredTotal 
                               FROM assignables a 
                               JOIN quotation q ON a.qutId = q.qutId 
                               JOIN clients c ON a.clientId = c.clientId 
                               WHERE 1=1";
        if (!empty($searchTerm)) {
            $filteredCountQuery .= " AND (c.clientName LIKE :search OR q.qutReference LIKE :search)";
        }
        if (strtolower($searchTerm) === 'pending') {
    $filteredCountQuery .= " OR (a.poRequired = 1 AND a.poPending = 1)";
}
        if (!empty($statuses)) {
            $filteredCountQuery .= " AND a.status IN (" . implode(',', $statusPlaceholders) . ")";
        }
        $filteredCountStmt = $conn->prepare($filteredCountQuery);
        if (!empty($searchTerm)) {
            $filteredCountStmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
        }
        if (!empty($statuses)) {
            foreach ($statuses as $index => $status) {
                $filteredCountStmt->bindValue(":status$index", $status, PDO::PARAM_STR);
            }
        }
        $filteredCountStmt->execute();
        $filteredCount = $filteredCountStmt->fetch(PDO::FETCH_ASSOC)['filteredTotal'];

        // Return response with debug info
        $response = [
            'data' => $assignables,
            'total' => $filteredCount,
            'page' => $page,
            'pageSize' => $pageSize,
            'debug' => $debug // Include debug info
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    } catch (PDOException $e) {
        $response = [
            'error' => $e->getMessage(),
            'debug' => $debug
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}



function insertComment($commentText, $commentBy, $commentDate, $assignableId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Parse the ISO 8601 date-time string, automatically adjusting for time zone
        $dateTime = new DateTime($commentDate);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $commentDate = $dateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to insert the comment
        $stmt = $conn->prepare("INSERT INTO assignableComments (commentText, commentBy, commentDate, assignableId) VALUES (?, ?, ?, ?)");
        $stmt->execute([$commentText, $commentBy, $commentDate, $assignableId]);
        
        // Return true on successful insertion
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}

function fetchComments($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch comments
        $stmt = $conn->prepare("SELECT * FROM assignableComments WHERE assignableId = ?");
        $stmt->execute([$assignableId]);
        
        // Return fetched comments
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function deleteComment($commentId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to delete the comment
        $stmt = $conn->prepare("DELETE FROM assignableComments WHERE commentId = ?");
        $stmt->execute([$commentId]);
        
        // Return true on successful deletion
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or deletion errors
        return false; // Return false if deletion fails
    }
}

function deleteCommentByTeam($teamId, $assignableId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to delete the comment
        $stmt = $conn->prepare("DELETE FROM assignableComments WHERE commentBy = ? AND assignableId = ?");
        $stmt->execute([$teamId, $assignableId]);
        
        // Return true on successful deletion
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or deletion errors
        return false; // Return false if deletion fails
    }
}

function fetchTeams() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch comments
        $stmt = $conn->prepare("SELECT * FROM teamConfiguration WHERE isActive = 1");
        $stmt->execute();
        
        // Return fetched comments
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function insertAssignment($teamId, $assignStatus, $active, $assignableId, $assignDate) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dateTime = new DateTime($assignDate);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $assignDate = $dateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to insert assignment data
        $stmt = $conn->prepare("INSERT INTO userAssignments (teamId, assignStatus, active, assignableId, assignDate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$teamId, $assignStatus, $active, $assignableId, $assignDate]);

        // Return the ID of the inserted assignment
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}

// Function to update assignment details
function updateAssignments($assignableId, $teamId, $assignStatus, $active, $assignDate) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dateTime = new DateTime($assignDate);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $assignDate = $dateTime->format('Y-m-d H:i:s');
        // Prepare the SQL query to update the assignment
        $stmt = $conn->prepare("UPDATE userAssignments SET teamId = ?, assignStatus = ?, active = ?, assignDate = ? WHERE assignableId = ?");
        $stmt->execute([$teamId, $assignStatus, $active,$assignDate, $assignableId]);
        
        // Check if any row was updated
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

// Function to update the status of an assignable
function updateAssignableStatus($assignableId, $status) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $cdateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        // Prepare the SQL query to update the status of the assignable
        $stmt = $conn->prepare("UPDATE assignables SET status = ?, last_modified = ? WHERE assignableId = ?");
        $stmt->execute([$status, $currentDate, $assignableId]);
        
        // Check if any row was updated
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

// Function to update the status of an assignable
function updateAssignablePodetails($assignableId, $poNumber, $poDate, $poFilePath) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if($poDate){
        $poDate = date('Y-m-d', strtotime($poDate));
        }else{
            $poDate = null;
        }
        $cdateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        // Prepare the SQL query to update the status of the assignable
        $stmt = $conn->prepare("UPDATE assignables SET poNumber = ?, poDate = ?, poFilePath = ?, poPending = 0, last_modified = ? WHERE assignableId = ?");
        $stmt->execute([$poNumber,$poDate, $poFilePath, $currentDate, $assignableId]);
        
        // Check if any row was updated
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

function fetchAssignmentsByAssignableId($assignableId, $teamId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Prepare and execute the SQL query to fetch assignments
        $stmt = $conn->prepare("SELECT ua.*, tc.teamName as executionTeamName, tc.teamId as executionTeamId FROM userAssignments ua LEFT JOIN teamConfiguration tc ON tc.teamId = ( SELECT teamId FROM userAssignments WHERE teamId != 5 and assignableId = ? LIMIT 1 ) WHERE ua.active = 1 AND ua.assignableId = ? AND ua.teamId = ?;");
        $stmt->execute([$assignableId,$assignableId, $teamId]);

        // Return fetched assignments
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function fetchAssignmentsByDetails($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Prepare and execute the SQL query to fetch assignments
        $stmt = $conn->prepare("SELECT ua.*, tc.teamName FROM userAssignments ua JOIN teamConfiguration tc ON ua.teamId = tc.teamId WHERE ua.active = 1 AND ua.assignableId = ? ORDER BY ua.assignDate DESC LIMIT 1");
        $stmt->execute([$assignableId]);

        // Return fetched assignments
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function fetchAllUserAssignments() {
  global $host, $dbName, $dbUname, $dbPass;

  try {
    $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all user assignments from the userAssignments table
    $stmt = $conn->query("SELECT q.qutReference, ua.*, tc.teamName, c.clientName, a.poDate, a.poNumber, a.poPending, a.subject, a.poRequired FROM userAssignments ua INNER JOIN assignables a ON ua.assignableId = a.assignableId INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId INNER JOIN clients c ON a.clientId = c.clientId INNER JOIN quotation q ON a.qutId = q.qutId WHERE ua.active = 1;");
    // Return fetched user assignments
    $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userAssignments as &$userAssignment) {
            if ($userAssignment['poRequired']) {
                if ($userAssignment['poPending']) {
                    $userAssignment['poNumber'] = 'Pending';
                    $userAssignment['poDate'] = 'Pending';
                }
            } else {
                $userAssignment['poNumber'] = 'Not Required';
                $userAssignment['poDate'] = 'Not Required';
            }
        }
    return $userAssignments;

  } catch (PDOException $e) {
    // Handle database connection errors or query errors
    return null; // Return null if fetching fails
  }
}

// function fetchAllUserAssignmentsNew() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Read JSON input (for POST request)
//         $jsonInput = file_get_contents("php://input");
//         $requestData = json_decode($jsonInput, true);

//         // Extract filters from requestData
//         $filters = isset($requestData['filters']) ? $requestData['filters'] : [];

//         $page = isset($filters['page']) ? (int)$filters['page'] : 1;
//         $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 10;
//         $search = isset($filters['searchTerm']) ? $filters['searchTerm'] : '';
//         $assignStatuses = isset($filters['statuses']) ? $filters['statuses'] : [];

//         $offset = ($page - 1) * $pageSize;

//         // Base query
//         $query = "SELECT q.qutReference, ua.*, tc.teamName, c.clientName, a.poDate, a.poNumber, a.poPending, a.subject, a.poRequired 
//                   FROM userAssignments ua 
//                   INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                   INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                   INNER JOIN clients c ON a.clientId = c.clientId 
//                   INNER JOIN quotation q ON a.qutId = q.qutId 
//                   WHERE ua.active = 1";

//         // Add search filter
//         if (!empty($search)) {
//             $query .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search)";
//         }

//         // Add assignStatus filter (handle multiple statuses dynamically)
//         $statusParams = [];
//         if (!empty($assignStatuses)) {
//             $statusPlaceholders = [];
//             foreach ($assignStatuses as $index => $status) {
//                 $paramName = ":assignStatus" . $index;
//                 $statusPlaceholders[] = $paramName;
//                 $statusParams[$paramName] = $status;
//             }
//             $query .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         // Add pagination
//         $query .= " LIMIT :offset, :pageSize";

//         $stmt = $conn->prepare($query);

//         // Bind parameters
//         if (!empty($search)) {
//             $searchTerm = "%$search%";
//             $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $stmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }
//         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

//         $stmt->execute();
//         $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Process PO status
//         foreach ($userAssignments as &$userAssignment) {
//             if ($userAssignment['poRequired']) {
//                 if ($userAssignment['poPending']) {
//                     $userAssignment['poNumber'] = 'Pending';
//                     $userAssignment['poDate'] = 'Pending';
//                 }
//             } else {
//                 $userAssignment['poNumber'] = 'Not Required';
//                 $userAssignment['poDate'] = 'Not Required';
//             }
//         }

//         // Get total count for pagination
//         $countQuery = "SELECT COUNT(*) AS total 
//                       FROM userAssignments ua 
//                       INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                       INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                       INNER JOIN clients c ON a.clientId = c.clientId 
//                       INNER JOIN quotation q ON a.qutId = q.qutId 
//                       WHERE ua.active = 1";

//         if (!empty($search)) {
//             $countQuery .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR ua.assignStatus LIKE :search)";
//         }
//         if (!empty($assignStatuses)) {
//             $countQuery .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         $countStmt = $conn->prepare($countQuery);

//         if (!empty($search)) {
//             $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $countStmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }

//         $countStmt->execute();
//         $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         return [
//             'data' => $userAssignments,
//             'total' => $totalCount,
//             'page' => $page,
//             'pageSize' => $pageSize
//         ];

//     } catch (PDOException $e) {
//         return json_encode(['error' => $e->getMessage()]);
//     }
// }


// function fetchAllUserAssignmentsNew() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Read JSON input (for POST request)
//         $jsonInput = file_get_contents("php://input");
//         $requestData = json_decode($jsonInput, true);

//         // Extract filters from requestData
//         $filters = isset($requestData['filters']) ? $requestData['filters'] : [];
//         $loggedUser = isset($filters['loggedUser']) ? $filters['loggedUser'] : '';
//         $page = isset($filters['page']) ? (int)$filters['page'] : 1;
//         $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 10;
//         $search = isset($filters['searchTerm']) ? $filters['searchTerm'] : '';
//         $assignStatuses = isset($filters['statuses']) ? $filters['statuses'] : [];

//         $offset = ($page - 1) * $pageSize;

//         // Base query
//         $query = "SELECT q.qutReference, ua.*, tc.teamName, c.clientName, a.poDate, a.poNumber, a.poPending, a.subject, a.poRequired 
//                   FROM userAssignments ua 
//                   INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                   INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                   INNER JOIN clients c ON a.clientId = c.clientId 
//                   INNER JOIN quotation q ON a.qutId = q.qutId 
//                   WHERE ua.active = 1";

//         // Add filter for non-master users
//         if ($loggedUser !== 'Master Admin') {
//             $query .= " AND tc.teamName = :loggedUser";
//         }

//         // Add search filter
//         if (!empty($search)) {
//             $query .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR tc.teamName LIKE :search )";
//         }

//         // Add assignStatus filter (handle multiple statuses dynamically)
//         $statusParams = [];
//         if (!empty($assignStatuses)) {
//             $statusPlaceholders = [];
//             foreach ($assignStatuses as $index => $status) {
//                 $paramName = ":assignStatus" . $index;
//                 $statusPlaceholders[] = $paramName;
//                 $statusParams[$paramName] = $status;
//             }
//             $query .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         // Add pagination
//         $query .= " LIMIT :offset, :pageSize";

//         $stmt = $conn->prepare($query);

//         // Bind parameters
//         if ($loggedUser !== 'Master Admin') {
//             $stmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
//         }
//         if (!empty($search)) {
//             $searchTerm = "%$search%";
//             $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $stmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }
//         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

//         $stmt->execute();
//         $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Process PO status
//         foreach ($userAssignments as &$userAssignment) {
//             if ($userAssignment['poRequired']) {
//                 if ($userAssignment['poPending']) {
//                     $userAssignment['poNumber'] = 'Pending';
//                     $userAssignment['poDate'] = 'Pending';
//                 }
//             } else {
//                 $userAssignment['poNumber'] = 'Not Required';
//                 $userAssignment['poDate'] = 'Not Required';
//             }
//         }

//         // Get total count for pagination
//         $countQuery = "SELECT COUNT(*) AS total 
//                       FROM userAssignments ua 
//                       INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                       INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                       INNER JOIN clients c ON a.clientId = c.clientId 
//                       INNER JOIN quotation q ON a.qutId = q.qutId 
//                       WHERE ua.active = 1";

//         // Add filter for non-master users in count query
//         if ($loggedUser !== 'Master Admin') {
//             $countQuery .= " AND tc.teamName = :loggedUser";
//         }

//         if (!empty($search)) {
//             $countQuery .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search)";
//         }
//         if (!empty($assignStatuses)) {
//             $countQuery .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         $countStmt = $conn->prepare($countQuery);

//         if ($loggedUser !== 'Master Admin') {
//             $countStmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
//         }
//         if (!empty($search)) {
//             $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $countStmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }

//         $countStmt->execute();
//         $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         return ([
//             'data' => $userAssignments,
//             'total' => $totalCount,
//             'page' => $page,
//             'pageSize' => $pageSize
//         ]);

//     } catch (PDOException $e) {
//         return json_encode(['error' => $e->getMessage()]);
//     }
// }

// function fetchAllUserAssignmentsNew() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Read JSON input (for POST request)
//         $jsonInput = file_get_contents("php://input");
//         $requestData = json_decode($jsonInput, true);

//         // Extract filters from requestData
//         $filters = isset($requestData['filters']) ? $requestData['filters'] : [];
//         $loggedUser = isset($filters['loggedUser']) ? $filters['loggedUser'] : '';
//         $page = isset($filters['page']) ? (int)$filters['page'] : 1;
//         $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 10;
//         $search = isset($filters['searchTerm']) ? $filters['searchTerm'] : '';
//         $assignStatuses = isset($filters['statuses']) ? $filters['statuses'] : [];

//         $offset = ($page - 1) * $pageSize;

//         // Base query
//         $query = "SELECT q.qutReference, ua.*, tc.teamName, c.clientName, a.poDate, a.poNumber, a.poPending, a.subject, a.poRequired 
//                   FROM userAssignments ua 
//                   INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                   INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                   INNER JOIN clients c ON a.clientId = c.clientId 
//                   INNER JOIN quotation q ON a.qutId = q.qutId 
//                   WHERE ua.active = 1";

//         // Add filter for non-master users
//         if ($loggedUser !== 'Master Admin') {
//             $query .= " AND tc.teamName = :loggedUser";
//         }

//         // Add search filter
//         if (!empty($search)) {
//             $query .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR tc.teamName LIKE :search )";
//         }
        
//         if (strtolower($search) === 'pending') {
//             $query .= " OR (a.poRequired = 1 AND a.poPending = 1)";
//         }
        
//         // Add assignStatus filter (handle multiple statuses dynamically)
//         $statusParams = [];
//         if (!empty($assignStatuses)) {
//             $statusPlaceholders = [];
//             foreach ($assignStatuses as $index => $status) {
//                 $paramName = ":assignStatus" . $index;
//                 $statusPlaceholders[] = $paramName;
//                 $statusParams[$paramName] = $status;
//             }
//             $query .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         // Add pagination
//         $query .= " LIMIT :offset, :pageSize";

//         $stmt = $conn->prepare($query);

//         // Bind parameters
//         if ($loggedUser !== 'Master Admin') {
//             $stmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
//         }
//         if (!empty($search)) {
//             $searchTerm = "%$search%";
//             $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $stmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }
//         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

//         $stmt->execute();
//         $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Process PO status
//         foreach ($userAssignments as &$userAssignment) {
//             if ($userAssignment['poRequired']) {
//                 if ($userAssignment['poPending']) {
//                     $userAssignment['poNumber'] = 'Pending';
//                     $userAssignment['poDate'] = 'Pending';
//                 }
//             } else {
//                 $userAssignment['poNumber'] = 'Not Required';
//                 $userAssignment['poDate'] = 'Not Required';
//             }
//         }

//         // Get total count for pagination
//         $countQuery = "SELECT COUNT(*) AS total 
//                       FROM userAssignments ua 
//                       INNER JOIN assignables a ON ua.assignableId = a.assignableId 
//                       INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
//                       INNER JOIN clients c ON a.clientId = c.clientId 
//                       INNER JOIN quotation q ON a.qutId = q.qutId 
//                       WHERE ua.active = 1";

//         // Add filter for non-master users in count query
//         if ($loggedUser !== 'Master Admin') {
//             $countQuery .= " AND tc.teamName = :loggedUser";
//         }

//         // ✅ FIXED: Add missing teamName search in count query
//         if (!empty($search)) {
//             $countQuery .= " AND (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR tc.teamName LIKE :search)";
//         }

//         if (!empty($assignStatuses)) {
//             $countQuery .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
//         }

//         $countStmt = $conn->prepare($countQuery);

//         if ($loggedUser !== 'Master Admin') {
//             $countStmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
//         }
//         if (!empty($search)) {
//             $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         foreach ($statusParams as $paramName => $value) {
//             $countStmt->bindValue($paramName, $value, PDO::PARAM_STR);
//         }

//         $countStmt->execute();
//         $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         return ([
//             'data' => $userAssignments,
//             'total' => $totalCount,
//             'page' => $page,
//             'pageSize' => $pageSize
//         ]);

//     } catch (PDOException $e) {
//         return json_encode(['error' => $e->getMessage()]);
//     }
// }

function fetchAllUserAssignmentsNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $jsonInput = file_get_contents("php://input");
        $requestData = json_decode($jsonInput, true);

        $filters = isset($requestData['filters']) ? $requestData['filters'] : [];
        $loggedUser = isset($filters['loggedUser']) ? $filters['loggedUser'] : '';
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 10;
        $search = isset($filters['searchTerm']) ? $filters['searchTerm'] : '';
        $assignStatuses = isset($filters['statuses']) ? $filters['statuses'] : [];

        $offset = ($page - 1) * $pageSize;

        $query = "SELECT q.qutReference, ua.*, tc.teamName, c.clientName, a.poDate, a.poNumber, a.poPending, a.subject, a.poRequired 
                  FROM userAssignments ua 
                  INNER JOIN assignables a ON ua.assignableId = a.assignableId 
                  INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
                  INNER JOIN clients c ON a.clientId = c.clientId 
                  INNER JOIN quotation q ON a.qutId = q.qutId 
                  WHERE ua.active = 1";

        if ($loggedUser !== 'Master Admin') {
            $query .= " AND tc.teamName = :loggedUser";
        }

        if (!empty($search)) {
            $query .= " AND ( (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR tc.teamName LIKE :search)";
            if (strtolower($search) === 'pending') {
                $query .= " OR (a.poRequired = 1 AND a.poPending = 1)";
            }
            $query .= ")";
        }

        $statusParams = [];
        if (!empty($assignStatuses)) {
            $statusPlaceholders = [];
            foreach ($assignStatuses as $index => $status) {
                $paramName = ":assignStatus" . $index;
                $statusPlaceholders[] = $paramName;
                $statusParams[$paramName] = $status;
            }
            $query .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
        }

        $query .= " LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);

        if ($loggedUser !== 'Master Admin') {
            $stmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
        }
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        }
        foreach ($statusParams as $paramName => $value) {
            $stmt->bindValue($paramName, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $userAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userAssignments as &$userAssignment) {
            if ($userAssignment['poRequired']) {
                if ($userAssignment['poPending']) {
                    $userAssignment['poNumber'] = 'Pending';
                    $userAssignment['poDate'] = 'Pending';
                }
            } else {
                $userAssignment['poNumber'] = 'Not Required';
                $userAssignment['poDate'] = 'Not Required';
            }
        }

        $countQuery = "SELECT COUNT(*) AS total 
                      FROM userAssignments ua 
                      INNER JOIN assignables a ON ua.assignableId = a.assignableId 
                      INNER JOIN teamConfiguration tc ON ua.teamId = tc.teamId 
                      INNER JOIN clients c ON a.clientId = c.clientId 
                      INNER JOIN quotation q ON a.qutId = q.qutId 
                      WHERE ua.active = 1";

        if ($loggedUser !== 'Master Admin') {
            $countQuery .= " AND tc.teamName = :loggedUser";
        }

        if (!empty($search)) {
            $countQuery .= " AND ( (c.clientName LIKE :search OR a.subject LIKE :search OR a.poNumber LIKE :search OR tc.teamName LIKE :search)";
            if (strtolower($search) === 'pending') {
                $countQuery .= " OR (a.poRequired = 1 AND a.poPending = 1)";
            }
            $countQuery .= ")";
        }

        if (!empty($assignStatuses)) {
            $countQuery .= " AND ua.assignStatus IN (" . implode(", ", $statusPlaceholders) . ")";
        }

        $countStmt = $conn->prepare($countQuery);

        if ($loggedUser !== 'Master Admin') {
            $countStmt->bindValue(':loggedUser', $loggedUser, PDO::PARAM_STR);
        }
        if (!empty($search)) {
            $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        }
        foreach ($statusParams as $paramName => $value) {
            $countStmt->bindValue($paramName, $value, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return ([
            'data' => $userAssignments,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ]);

    } catch (PDOException $e) {
        return json_encode(['error' => $e->getMessage()]);
    }
}


// Function to insert an invoice into the invoiceList table
function insertInvoice($invoiceData) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Prepare and execute the SQL query to insert invoice data
        $dateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $invoiceDate = $dateTime->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO invoiceList (invoiceNumber, invoiceFilePath, assignableId, invoiceDate, paymentStatus) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$invoiceData['invoiceNumber'], $invoiceData['invoiceFilePath'], $invoiceData['assignableId'], $invoiceDate]);
        
        // Return the ID of the inserted invoice
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return null; // Return null if insertion fails
    }
}

// Function to delete an invoice from the invoiceList table
function deleteInvoice($invoiceId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to delete the invoice
        $stmt = $conn->prepare("DELETE FROM invoiceList WHERE invoiceId = ?");
        $stmt->execute([$invoiceId]);
        
        // Return true on successful deletion
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or deletion errors
        return false; // Return false if deletion fails
    }
}

// Function to fetch invoices based on assignableId
function fetchInvoicesByAssignableId($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch invoices
        $stmt = $conn->prepare("SELECT * FROM invoiceList WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Return fetched invoices
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

// Function to update assignment details
function updateUserAssignmentStatus($assignableId, $teamId, $assignStatus) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $currentTimestamp = date("Y-m-d H:i:s");

        // Determine which column to update based on assignStatus
        switch ($assignStatus) {
            case 'IN PROGRESS':
                // Prepare the SQL query to update the assignment
                $stmt = $conn->prepare("UPDATE userAssignments SET  assignStatus = ?, inProgressDate = ? WHERE assignableId = ? and teamId = ?");
                $stmt->execute([ $assignStatus, $currentTimestamp, $assignableId, $teamId]);
                break;
            case 'DONE':
                // Prepare the SQL query to update the assignment
                $stmt = $conn->prepare("UPDATE userAssignments SET assignStatus = ?, completeDate = ? WHERE assignableId = ? and teamId = ?");
                $stmt->execute([ $assignStatus, $currentTimestamp, $assignableId, $teamId]);
                break;
            default:
                // Prepare the SQL query to update the assignment
                $stmt = $conn->prepare("UPDATE userAssignments SET assignStatus = ? WHERE assignableId = ? and teamId = ?");
                $stmt->execute([ $assignStatus, $assignableId, $teamId]);
        }
        
        // Check if any row was updated
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

// Function to update assignment details
function deleteUserAssignment($userAssignmentId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("Delete from userAssignments WHERE userAssignmentId = ?");
                $stmt->execute([$userAssignmentId]);
        
        // Check if any row was updated
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

function deleteAssignableByAssignableId($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        // Create a new PDO connection
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin a transaction
        $conn->beginTransaction();

        // Select qutId from assignables
        $stmt = $conn->prepare("SELECT qutId FROM assignables WHERE assignableId = ?");
        $stmt->execute([$assignableId]);
        $qutId = $stmt->fetchColumn();

        if ($qutId !== false) {
            // Update quotation table with status = 'open'
            $stmt = $conn->prepare("UPDATE quotation SET status = 'Open' WHERE qutId = ?");
            $stmt->execute([$qutId]);
        } else {
            // qutId not found, rollback and return false
            $conn->rollBack();
            return false;
        }

        // Delete from userAssignments
        $stmt = $conn->prepare("DELETE FROM userAssignments WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Delete from assignableItems
        $stmt = $conn->prepare("DELETE FROM assignableItems WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Delete from assignables
        $stmt = $conn->prepare("DELETE FROM assignables WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Delete from assignableComments
        $stmt = $conn->prepare("DELETE FROM assignableComments WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Delete from invoiceList
        $stmt = $conn->prepare("DELETE FROM invoiceList WHERE assignableId = ?");
        $stmt->execute([$assignableId]);

        // Commit the transaction
        $conn->commit();

        // Return true indicating success
        return true;
    } catch (PDOException $e) {
        // Rollback the transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Log the error message
        error_log("PDOException: " . $e->getMessage());
        // Return false indicating failure
        return false;
    }
}

function insertPayment($paymentData) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dateTime = new DateTime($paymentData['paymentDate']);
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $paymentDate = $dateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to insert payment data
        $stmt = $conn->prepare("INSERT INTO invoicePayment (paymentDate,invoiceId, amount, assignableId, qutReference) VALUES (?,?, ?, ?, ?)");
        $stmt->execute([$paymentDate ,$paymentData['invoiceId'], $paymentData['amount'], $paymentData['assignableId'], $paymentData['qutReference']]);
        
        // Return true if insertion was successful
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        error_log("PDOException: " . $e->getMessage());
        return false; // Return false if insertion fails
    }
}



function deletePayment($paymentId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to delete the payment
        $stmt = $conn->prepare("DELETE FROM invoicePayment WHERE paymentId = ?");
        $stmt->execute([$paymentId]);
        
        // Return true if deletion was successful
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or deletion errors
        error_log("PDOException: " . $e->getMessage());
        return false; // Return false if deletion fails
    }
}


function updatePayment($paymentId, $paymentData) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL query to update payment details
        $stmt = $conn->prepare("UPDATE invoicePayment SET paymentDate = ?, amount = ?,invoiceId = ?, assignableId = ?, qutReference = ? WHERE paymentId = ?");
        $stmt->execute([$paymentData['paymentDate'], $paymentData['amount'],$paymentData['invoiceId'], $paymentData['assignableId'], $paymentData['qutReference'], $paymentId]);
        
        // Return true if update was successful
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        error_log("PDOException: " . $e->getMessage());
        return false; // Return false if update fails
    }
}

function updateInvoicePaymentStatus($invoiceId, $paymentStatus) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Prepare the SQL query to update payment details
        $stmt = $conn->prepare("UPDATE invoiceList SET paymentStatus = ? WHERE invoiceId = ?");
        $stmt->execute([$paymentStatus, $invoiceId]);
        
        // Return true if update was successful
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        error_log("PDOException: " . $e->getMessage());
        return false; // Return false if update fails
    }
}

function fetchInvoicesWithPaymentsByInvoiceId($invoiceId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch invoice details
        $stmt = $conn->prepare("SELECT il.*, q.qutReference, c.clientName FROM invoiceList il LEFT JOIN assignables a ON il.assignableId = a.assignableId LEFT JOIN quotation q ON a.qutId = q.qutId LEFT JOIN clients c ON a.clientId = c.clientId where il.invoiceId = ?");
        $stmt->execute([$invoiceId]);

        // Fetch all invoices as an associative array
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Array to store final result
        $invoicesWithPayments = [];

        // Loop through each invoice
        foreach ($invoices as $invoice) {
            $invoiceId = $invoice['invoiceId'];

            // Fetch payments for current invoiceId
            $stmtPayments = $conn->prepare("SELECT paymentId, paymentDate, amount FROM invoicePayment WHERE invoiceId = :invoiceId");
            $stmtPayments->bindParam(':invoiceId', $invoiceId);
            $stmtPayments->execute();

            // Fetch payments as an associative array
            $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

            // Add invoice details and payments to final result
            $invoicesWithPayments[] = [
                'invoiceId' => $invoiceId,
                'invoiceDate' => $invoice['invoiceDate'],
                'invoiceNumber' => $invoice['invoiceNumber'],
                'invoiceFilePath' => $invoice['invoiceFilePath'],
                'paymentStatus' => $invoice['paymentStatus'],
                'qutReference' => $invoice['qutReference'],
                'clientName' => $invoice['clientName'],
                'assignableId' => $invoice['assignableId'],
                'payments' => $payments // Include payments for current invoiceId
            ];
        }

        // Return array of invoices with payments
        return $invoicesWithPayments;
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        error_log("PDOException: " . $e->getMessage());
        return []; // Return an empty array if fetching fails
    }
}

function fetchAllInvoicesWithPayments() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL query to fetch invoice details
        $stmt = $conn->prepare("SELECT il.invoiceId, il.invoiceDate, il.invoiceNumber, il.invoiceFilePath, il.paymentStatus, q.qutReference, a.assignableId, c.clientName FROM invoiceList il LEFT JOIN assignables a ON il.assignableId = a.assignableId LEFT JOIN quotation q ON a.qutId = q.qutId LEFT JOIN clients c ON a.clientId = c.clientId;");
        $stmt->execute();

        // Fetch all invoices as an associative array
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Array to store final result
        $invoicesWithPayments = [];

        // Loop through each invoice
        foreach ($invoices as $invoice) {
            $invoiceId = $invoice['invoiceId'];

            // Fetch payments for current invoiceId
            $stmtPayments = $conn->prepare("SELECT paymentId, paymentDate, amount FROM invoicePayment WHERE invoiceId = :invoiceId");
            $stmtPayments->bindParam(':invoiceId', $invoiceId);
            $stmtPayments->execute();

            // Fetch payments as an associative array
            $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

            // Add invoice details and payments to final result
            $invoicesWithPayments[] = [
                'invoiceId' => $invoiceId,
                'invoiceDate' => $invoice['invoiceDate'],
                'invoiceNumber' => $invoice['invoiceNumber'],
                'invoiceFilePath' => $invoice['invoiceFilePath'],
                'paymentStatus' => $invoice['paymentStatus'],
                'qutReference' => $invoice['qutReference'],
                'clientName' => $invoice['clientName'],
                'payments' => $payments // Include payments for current invoiceId
            ];
        }

        // Return array of invoices with payments
        return $invoicesWithPayments;
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        error_log("PDOException: " . $e->getMessage());
        return []; // Return an empty array if fetching fails
    }
}

function fetchAllInvoicesWithPaymentsNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Read JSON payload from POST request
        $jsonInput = file_get_contents("php://input");
        $requestData = json_decode($jsonInput, true);

        // Extract filters
        $filters = isset($requestData['filters']) ? $requestData['filters'] : [];

        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $pageSize = isset($filters['pageSize']) ? (int)$filters['pageSize'] : 10;
        $search = isset($filters['searchTerm']) ? $filters['searchTerm'] : '';
        $paymentStatuses = isset($filters['statuses']) ? $filters['statuses'] : [];

        $offset = ($page - 1) * $pageSize;

        // Base query
        $query = "SELECT il.invoiceId, il.invoiceDate, il.invoiceNumber, il.invoiceFilePath, il.paymentStatus, 
                         q.qutReference, a.assignableId, c.clientName 
                  FROM invoiceList il 
                  LEFT JOIN assignables a ON il.assignableId = a.assignableId 
                  LEFT JOIN quotation q ON a.qutId = q.qutId 
                  LEFT JOIN clients c ON a.clientId = c.clientId 
                  WHERE 1";

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (c.clientName LIKE :search OR il.invoiceNumber LIKE :search OR q.qutReference LIKE :search OR q.invoiceDate LIKE :search)";
        }

        // Handle multiple statuses
        $statusParams = [];
        if (!empty($paymentStatuses)) {
            $statusPlaceholders = [];
            foreach ($paymentStatuses as $index => $status) {
                $paramName = ":status" . $index;
                $statusPlaceholders[] = $paramName;
                $statusParams[$paramName] = $status;
            }
            $query .= " AND il.paymentStatus IN (" . implode(", ", $statusPlaceholders) . ")";
        }

        // Add pagination
        $query .= " LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);

        // Bind parameters
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        }
        foreach ($statusParams as $paramName => $value) {
            $stmt->bindValue($paramName, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch payments for each invoice
        foreach ($invoices as &$invoice) {
            $invoiceId = $invoice['invoiceId'];

            $stmtPayments = $conn->prepare("SELECT paymentId, paymentDate, amount FROM invoicePayment WHERE invoiceId = :invoiceId");
            $stmtPayments->bindValue(':invoiceId', $invoiceId, PDO::PARAM_INT);
            $stmtPayments->execute();

            $invoice['payments'] = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) AS total FROM invoiceList il 
                       LEFT JOIN assignables a ON il.assignableId = a.assignableId 
                       LEFT JOIN quotation q ON a.qutId = q.qutId 
                       LEFT JOIN clients c ON a.clientId = c.clientId 
                       WHERE 1";

        if (!empty($search)) {
            $countQuery .= " AND (c.clientName LIKE :search OR il.invoiceNumber LIKE :search OR q.qutReference LIKE :search)";
        }
        if (!empty($paymentStatuses)) {
            $countQuery .= " AND il.paymentStatus IN (" . implode(", ", $statusPlaceholders) . ")";
        }

        $countStmt = $conn->prepare($countQuery);

        if (!empty($search)) {
            $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        }
        foreach ($statusParams as $paramName => $value) {
            $countStmt->bindValue($paramName, $value, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data' => $invoices,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];

    } catch (PDOException $e) {
        return json_encode(['error' => $e->getMessage()]);
    }
}



function getRemarks($assignableId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT ua.remarks FROM userAssignments ua JOIN teamConfiguration tc ON ua.teamId = tc.teamId where tc.teamName != 'Account Team' and ua.assignableId = :assignableId");
        $stmt->bindParam(':assignableId', $assignableId);
        $stmt->execute();
        $remarks = $stmt->fetchColumn();
        
        return $remarks; 
    } catch (PDOException $e) {
        return null; 
    }
}

function updateRemarks($assignableId, $remarks) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL query to update remarks details
        $stmt = $conn->prepare("UPDATE userAssignments SET remarks = ? WHERE assignableId = ?");
        $stmt->execute([$remarks, $assignableId]);
        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        error_log("PDOException: " . $e->getMessage());
        return false; // Return false if update fails
    }
}

?>
