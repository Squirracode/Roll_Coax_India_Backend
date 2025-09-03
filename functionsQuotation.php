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
function insertQuotationItem($quotationId, $item,$sacCode, $uom, $qty, $unit_price) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO quotationItems (qutId, itemDetails, sacCode, uom, qty, unitRate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quotationId, $item,$sacCode, $uom, $qty, $unit_price]);
        
        return true; // Return true on successful insertion
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}

function updateQuotation($qutId,$quotationData, $itemsData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $quotationDate = date('Y-m-d', strtotime($quotationData['date']));
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $last_modified = $dateTime->format('Y-m-d H:i:s'); 
        // Prepare and execute the SQL query to update quotation data
        $stmt = $conn->prepare("UPDATE quotation SET qutReference = ?, date = ?, status = ?, subject = ?, clientId = ?, contactName = ?, termAndCondition = ?, scopeOfWork = ?, version = ?, created_by = ?, last_modified = ? WHERE qutId = ?");
        $stmt->execute([$quotationData['qutReference'], $quotationDate, $quotationData['status'], $quotationData['subject'], $quotationData['clientId'], $quotationData['contactName'], $quotationData['termAndCondition'], $quotationData['scopeOfWork'], $quotationData['version'], $quotationData['createdBy'], $last_modified, $qutId]);

        // Delete existing items related to the quotation
        $stmt = $conn->prepare("DELETE FROM quotationItems WHERE qutId = ?");
        $stmt->execute([$qutId]);

        // Insert updated items related to the quotation
        foreach ($itemsData as $item) {
            $stmt = $conn->prepare("INSERT INTO quotationItems (qutId, itemDetails, sacCode, uom, qty, unitRate) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$qutId, $item['itemDetails'], $item['sacCode'], $item['uom'], $item['qty'], $item['unitRate']]);
        }

        return true; // Return true on successful update
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

// Function to insert a quotation into the database
function insertQuotation($quotationData) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dateTime = new DateTime($quotationData['date']);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $quotationDate = $dateTime->format('Y-m-d H:i:s');
        
        $cdateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to insert quotation data
        $stmt = $conn->prepare("INSERT INTO quotation (qutReference, date, subject, status, clientId, contactName, scopeOfWork, termAndCondition, version, created_at, last_modified, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quotationData['qutReference'], $quotationDate, $quotationData['subject'], $quotationData['status'], $quotationData['clientId'], $quotationData['contactName'], $quotationData['scopeOfWork'], $quotationData['termAndCondition'], $quotationData['version'], $currentDate, $currentDate, $quotationData['createdBy']]);
        // Return the ID of the inserted quotation
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        echo $e;
        return null; // Return null if insertion fails
    }
}


function fetchQuotationDetails($qutId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch quotation details
        $stmt = $conn->prepare("SELECT * FROM quotation WHERE qutId = ?");
        $stmt->execute([$qutId]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch items associated with the quotation
        $stmt = $conn->prepare("SELECT * FROM quotationItems WHERE qutId = ?");
        $stmt->execute([$qutId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Construct the quotation object
        $quotationObject = array(
            'quotation' => $quotation,
            'items' => $items
        );

        return $quotationObject; // Return quotation details along with items
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function updateQuotationStatus($qutId, $status) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("UPDATE quotation SET status = ? WHERE qutId = ?");
        $stmt->execute([$status, $qutId]);

        return true; // Return true on successful update
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}


// Function to delete a quotation from the database
function deleteQuotation($qutId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM quotation WHERE qutId = ?");
        $stmt->execute([$qutId]);

        return true; // Return true on successful deletion
    } catch (PDOException $e) {
        // Handle database connection errors or deletion errors
        return false; // Return false if deletion fails
    }
}

// Function to fetch all quotations
function fetchAllQuotations() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("SELECT q.*, c.clientName FROM quotation q JOIN clients c ON q.clientId = c.clientId WHERE q.status != 'Draft'");
        $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $quotations; // Return all quotations
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

// function fetchAllQuotationslist() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Get request parameters from POST
//         $data = json_decode(file_get_contents("php://input"), true) ?: [];
//         $searchTerm = isset($data['searchTerm']) ? trim($data['searchTerm']) : '';
//         $statuses = isset($data['statuses']) ? $data['statuses'] : [];
//         $page = isset($data['page']) ? (int)$data['page'] : 1;
//         $pageSize = isset($data['pageSize']) ? (int)$data['pageSize']:10;

//         // Base query
//         $query = "SELECT q.*, c.clientName 
//                   FROM quotation q 
//                   JOIN clients c ON q.clientId = c.clientId 
//                   WHERE q.status != 'Draft'";

//         // Add search filter
//         if ($searchTerm !== '') {
//             $query .= " AND (q.qutReference LIKE :searchTerm 
//                           OR q.subject LIKE :searchTerm 
//                           OR q.scopeOfWork LIKE :searchTerm 
//                           OR q.created_by LIKE :searchTerm 
//                           OR q.created_at LIKE :searchTerm 
//                           OR q.status LIKE :searchTerm 
//                           OR c.clientName LIKE :searchTerm)";
//         }

//         // Add status filter with named parameters
//         if (!empty($statuses)) {
//             $placeholders = [];
//             foreach ($statuses as $index => $status) {
//                 $placeholders[] = ":status$index";
//             }
//             $query .= " AND q.status IN (" . implode(',', $placeholders) . ")";
//         }

//         // Count total items for pagination
//         $countStmt = $conn->prepare("SELECT COUNT(*) FROM ($query) AS total");
//         if ($searchTerm !== '') {
//             $countStmt->bindValue(':searchTerm', "%$searchTerm%");
//         }
//         if (!empty($statuses)) {
//             foreach ($statuses as $index => $status) {
//                 $countStmt->bindValue(":status$index", $status);
//             }
//         }
//         $countStmt->execute();
//         $totalItems = $countStmt->fetchColumn();

//         // Add pagination
//         $offset = ($page - 1) * $pageSize;
//         $query .= " LIMIT :offset, :pageSize";

//         // Prepare and execute the main query
//         $stmt = $conn->prepare($query);
//         if ($searchTerm !== '') {
//             $stmt->bindValue(':searchTerm', "%$searchTerm%");
//         }
//         if (!empty($statuses)) {
//             foreach ($statuses as $index => $status) {
//                 $stmt->bindValue(":status$index", $status);
//             }
//         }
//         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
//         $stmt->execute();
//         $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Return response with data and total count
//         return [
//             'data' => $quotations,
//             'total' => $totalItems
//         ];
//     } catch (PDOException $e) {
//         http_response_code(500);
//         return ['error' => 'Database error: ' . $e->getMessage()];
//     }
// }

function fetchAllQuotationslist() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $searchTerm = isset($data['searchTerm']) ? trim($data['searchTerm']) : '';
        $statuses = isset($data['statuses']) ? $data['statuses'] : [];
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $pageSize = isset($data['pageSize']) ? (int)$data['pageSize'] : 10;

        // Base query
        $query = "SELECT q.*, c.clientName 
                  FROM quotation q 
                  JOIN clients c ON q.clientId = c.clientId 
                  WHERE q.status != 'Draft'";

        $countQuery = "SELECT COUNT(*) FROM quotation q JOIN clients c ON q.clientId = c.clientId WHERE q.status != 'Draft'";

        $params = [];

        // Add search filter
        if (!empty($searchTerm)) {
            $searchCondition = " AND (q.qutReference LIKE :searchTerm 
                                OR q.subject LIKE :searchTerm 
                                OR q.scopeOfWork LIKE :searchTerm 
                                OR q.created_by LIKE :searchTerm 
                                OR q.created_at LIKE :searchTerm 
                                OR q.status LIKE :searchTerm 
                                OR c.clientName LIKE :searchTerm)";
            $query .= $searchCondition;
            $countQuery .= $searchCondition;
            $params[':searchTerm'] = "%$searchTerm%";
        }

        // Add status filter
        if (!empty($statuses)) {
            $statusPlaceholders = [];
            foreach ($statuses as $index => $status) {
                $statusPlaceholders[] = ":status$index";
                $params[":status$index"] = $status;
            }
            $query .= " AND q.status IN (" . implode(',', $statusPlaceholders) . ")";
            $countQuery .= " AND q.status IN (" . implode(',', $statusPlaceholders) . ")";
        }

        // Execute count query
        $countStmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalItems = $countStmt->fetchColumn();

        // Add pagination
        $offset = ($page - 1) * $pageSize;
        $query .= " LIMIT :offset, :pageSize";

        // Prepare and execute main query
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $quotations,
            'total' => $totalItems
        ];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}


// Function to fetch all quotations
function fetchAllDraftQuotations() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("SELECT * FROM quotation where status = 'Draft'");
        $quotations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $quotations; // Return all quotations
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function getNextQuotationNumber() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $currentYear = date('Y');
        // Query to fetch the maximum qutReference
        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(qutReference, '/', -1), ' ', 1) AS UNSIGNED)) AS max_qut_ref 
            FROM quotation 
            WHERE qutReference LIKE :year_prefix
        ");
        $stmt->execute([':year_prefix' => "%/$currentYear/%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Extract the maximum numeric part of the qutReference
        $maxQuotationRef = $result['max_qut_ref'];

        if (!$maxQuotationRef) {
            $nextQuotationRef = 1;
        } else {
            // Increment the maximum numeric part by 1
            $nextQuotationRef = $maxQuotationRef + 1;
        }

        return $nextQuotationRef;
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

function fetchQuotationVersionInfoByReference($qutReference) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch the version, qutId, and created_at based on qutReference
        $stmt = $conn->prepare("SELECT version, qutId, created_at FROM quotation WHERE qutReference = ?");
        $stmt->execute([$qutReference]);
        $quotationInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        return $quotationInfo; // Return the version, qutId, and created_at
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function fetchScopeOfWorkById($templateId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch the scope of work based on templateId
        $stmt = $conn->prepare("SELECT templateItem FROM scopeOfWork WHERE templateId = ?");
        $stmt->execute([$templateId]);
        $scopeOfWorkItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $templateItems = array_column($scopeOfWorkItems, 'templateItem');

        return $templateItems; // Return the array of template items
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function fetchAllScopeOfWork() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all scope of work templates
        $stmt = $conn->query("SELECT * FROM scopeOfWork");
        $scopeOfWorkTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $scopeOfWorkTemplates; // Return all scope of work templates
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function fetchAllTermConditions() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all term conditions
        $stmt = $conn->query("SELECT * FROM termAndCondition");
        $termConditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $termConditions; // Return all term conditions
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null; // Return null if fetching fails
    }
}

function updateQuotationStatusToClosed($qutId, $reasonForClose) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update the quotation status to "Closed" and save the closed remarks
        $stmt = $conn->prepare("UPDATE quotation SET status = 'Closed', reasonForClose = ? WHERE qutId = ?");
        $stmt->execute([$reasonForClose, $qutId]);

        return true; // Return true on successful update
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false; // Return false if update fails
    }
}

function insertInquiry($inquiryData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Parse the ISO 8601 date-time string, automatically adjusting for time zone
        $dateTime = new DateTime($inquiryData['inquiryDate']);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $inquiryDate = $dateTime->format('Y-m-d H:i:s');
        $cdateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to insert inquiry data
        $stmt = $conn->prepare("INSERT INTO inquiry (inquiryDate, status, clientId, contactId, workDescription, remarks, created_at, last_modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inquiryDate, $inquiryData['status'], $inquiryData['clientId'], $inquiryData['contactId'], $inquiryData['workDescription'], $inquiryData['remarks'], $currentDate, $currentDate]);
        
        // Return the ID of the inserted inquiry
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return null; // Return null if insertion fails
    }
}

function fetchAllInquiries() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all inquiries
        $stmt = $conn->query("SELECT i.*, c.clientName, con.contactPersonName as contactName, con.contactNumber FROM inquiry i JOIN clients c ON i.clientId = c.clientId JOIN contact con ON i.contactId = con.contactId");
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $inquiries; // Return all inquiries
    } catch (PDOException $e) {
        return null; // Return null if fetching fails
    }
}

function fetchAllInquiriesList() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get request parameters from POST
        $data = json_decode(file_get_contents("php://input"), true) ?: [];

$searchTerm = isset($data['searchTerm']) ? trim($data['searchTerm']) : '';
$statuses = isset($data['statuses']) ? $data['statuses'] : [];
$page = isset($data['page']) ? (int)$data['page'] : 1;
$pageSize = isset($data['pageSize']) ? (int)$data['pageSize'] : 10;

        // Base query
        $query = "SELECT i.*, c.clientName, con.contactPersonName AS contactName, con.contactNumber 
                  FROM inquiry i 
                  JOIN clients c ON i.clientId = c.clientId 
                  JOIN contact con ON i.contactId = con.contactId 
                  WHERE 1=1";

        $params = [];

        // Add search filter
        if ($searchTerm !== '') {
            $query .= " AND (c.clientName LIKE :searchTerm 
                          OR con.contactPersonName LIKE :searchTerm 
                          OR i.workDescription LIKE :searchTerm 
                          OR i.remarks LIKE :searchTerm 
                          OR i.inquiryDate LIKE :searchTerm 
                          OR i.status LIKE :searchTerm 
                          OR con.contactNumber LIKE :searchTerm)";
            $params[':searchTerm'] = "%$searchTerm%";
        }

        // Add status filter
        if (!empty($statuses)) {
            $placeholders = [];
            foreach ($statuses as $index => $status) {
                $placeholders[] = ":status$index";
                $params[":status$index"] = $status;
            }
            $query .= " AND i.status IN (" . implode(',', $placeholders) . ")";
        }

        // Count total items for pagination
        $countQuery = "SELECT COUNT(*) FROM inquiry i 
                       JOIN clients c ON i.clientId = c.clientId 
                       JOIN contact con ON i.contactId = con.contactId 
                       WHERE 1=1";

        if ($searchTerm !== '') {
            $countQuery .= " AND (c.clientName LIKE :searchTerm 
                                OR con.contactPersonName LIKE :searchTerm 
                                OR i.workDescription LIKE :searchTerm 
                                OR i.remarks LIKE :searchTerm 
                                OR i.inquiryDate LIKE :searchTerm 
                                OR i.status LIKE :searchTerm 
                                OR con.contactNumber LIKE :searchTerm)";
        }
        if (!empty($statuses)) {
            $countQuery .= " AND i.status IN (" . implode(',', $placeholders) . ")";
        }

        $countStmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalItems = $countStmt->fetchColumn();

        // Add pagination
        $offset = ($page - 1) * $pageSize;
        $query .= " LIMIT :offset, :pageSize";

        // Prepare and execute the main query
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return JSON response
        echo json_encode([
            'data' => $inquiries,
            'total' => $totalItems
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function fetchAllInquiriesForExcel() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all inquiries
        $stmt = $conn->query('SELECT i.inquiryDate AS "Inquiry Date", c.clientName AS "Client Name", con.contactPersonName AS "Contact Name", con.contactNumber AS "Contact Number", i.inquiryDate AS "Inquiry Date", i.status AS "Status", i.workDescription AS "Work Description", i.remarks AS "Remarks", i.created_at AS "Created Date", i.last_modified AS "Modified Date", i.closeComments AS "Close Comment" FROM inquiry i JOIN clients c ON i.clientId = c.clientId JOIN contact con ON i.contactId = con.contactId');
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $inquiries; // Return all inquiries
    } catch (PDOException $e) {
        return null; // Return null if fetching fails
    }
}
// Update an existing inquiry in the database
function updateInquiry($inquiryId, $inquiryData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $dateTime = new DateTime($inquiryData['inquiryDate']);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $inquiryDate = $dateTime->format('Y-m-d H:i:s');
        
        $cdateTime = new DateTime();
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        // Prepare and execute the SQL query to update inquiry data
        $stmt = $conn->prepare("UPDATE inquiry SET inquiryDate = ?, status = ?, clientId = ?, contactId = ?, workDescription = ?, remarks = ?, last_modified = ? WHERE inquiryId = ?");
        $stmt->execute([$inquiryDate, $inquiryData['status'], $inquiryData['clientId'], $inquiryData['contactId'], $inquiryData['workDescription'], $inquiryData['remarks'], $currentDate, $inquiryId]);

        return true; // Return true on successful update
    } catch (PDOException $e) {
        return false; // Return false if update fails
    }
}

function updateInquiryStatus($inquiryId, $inquiryData) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $cdateTime = new DateTime();
        $cdateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $cdateTime->format('Y-m-d H:i:s');
        
        // Prepare and execute the SQL query to update inquiry data
        $stmt = $conn->prepare("UPDATE inquiry SET closeComments = ?, status = ?, last_modified = ? WHERE inquiryId = ?");
        $stmt->execute([$inquiryData['closeComments'], $inquiryData['status'], $currentDate, $inquiryId]);

        return true; // Return true on successful update
    } catch (PDOException $e) {
        return false; // Return false if update fails
    }
}

function fetchInquiryById($inquiryId) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT i.*, c.clientName, con.contactPersonName as contactName 
                                FROM inquiry i 
                                JOIN clients c ON i.clientId = c.clientId 
                                JOIN contact con ON i.contactId = con.contactId 
                                WHERE i.inquiryId = ?");
        
        // Execute the query with parameter binding
        $stmt->execute([$inquiryId]);

        // Fetch all rows as associative array
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $inquiries; // Return the fetched inquiries (array)
    } catch (PDOException $e) {
        // Handle PDOException (database connection or query error)
        error_log('Database error: ' . $e->getMessage());
        return null; // Return null if fetching fails
    }
}

function deleteInquiry($inquiryId) {
    global $host, $dbName, $dbUname, $dbPass;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Prepare SQL statement to delete the inquiry
        $stmt = $conn->prepare("DELETE FROM inquiry WHERE inquiryId = ?");
        $stmt->execute([$inquiryId]);
        
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



?>
