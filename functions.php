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


function addUser($username, $password, $email, $name, $role) {
    
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, name, role) VALUES (:username, :password, :email, :name, :role)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':role', $role);

        $stmt->execute();

        return true; // Return true on successful insertion
    } catch (PDOException $e) {
        echo $e;
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}


// Function to get user details from the database
function getUserDetails($username) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    } catch (PDOException $e) {
        // Handle database connection errors
        return null;
    }
}

function getAllUsers() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $users; // Return the fetched users
    } catch (PDOException $e) {
        echo $e;
        // Handle database connection errors or query errors
        return false; // Return false if the query fails
    }
}

function getUserById($userId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM users where id = :userId");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    } catch (PDOException $e) {
        echo $e;
        // Handle database connection errors or query errors
        return false; // Return false if the query fails
    }
}

function updateUser($Id, $username, $password, $email, $role, $name) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, email = :email, name = :name, role = :role WHERE id = :id");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $Id);

        $stmt->execute();

        return true; // Return true on successful update
    } catch (PDOException $e) {
        // Log the exception message
        error_log('PDOException: ' . $e->getMessage());
        return false; // Return false if update fails
    }
}




// Add client and contacts
function addClientAndContacts($clientName, $address, $city, $state, $gst, $pan, $contacts,$areas) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Start a transaction
        $conn->beginTransaction();
        $dateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $createdDate = $dateTime->format('Y-m-d H:i:s');
        // Insert into clients table
        $sql_client = "INSERT INTO clients (clientName, address, city, state, gst, pan, createdDate) VALUES (:clientName, :address, :city, :state, :gst, :pan, :createdDate)";
        $stmt_client = $conn->prepare($sql_client);
        $stmt_client->bindParam(':clientName', $clientName);
        $stmt_client->bindParam(':address', $address);
        $stmt_client->bindParam(':city', $city);
        $stmt_client->bindParam(':state', $state);
        $stmt_client->bindParam(':gst', $gst);
        $stmt_client->bindParam(':pan', $pan);
        $stmt_client->bindParam(':createdDate', $createdDate);
        $stmt_client->execute();

        $clientId = $conn->lastInsertId();

        // Insert contacts
        $sql_contact = "INSERT INTO contact (contactPersonName, contactNumber, email, clientId) VALUES (:contactPersonName, :contactNumber, :email, :clientId)";
        $stmt_contact = $conn->prepare($sql_contact);
        $stmt_contact->bindParam(':contactPersonName', $contactPersonName);
        $stmt_contact->bindParam(':contactNumber', $contactNumber);
        $stmt_contact->bindParam(':email', $email);
        $stmt_contact->bindParam(':clientId', $clientId);

        foreach ($contacts as $contact) {
            $contactPersonName = $contact['contactPersonName'];
            $contactNumber = $contact['contactNumber'];
            $email = $contact['email'];
            $stmt_contact->execute();
        }
        
        // Areas
        $stmt_area = $conn->prepare("INSERT INTO area (areaName, clientId) VALUES (:areaName, :clientId)");
        foreach ($areas as $a) {
            $stmt_area->execute([
                ':areaName' => $a['areaName'],
                ':clientId' => $clientId
            ]);
        }

        // Commit the transaction if all inserts are successful
        $conn->commit();
        return "Client, contacts & Areas added successfully";
    } catch (PDOException $e) {
        // Rollback the transaction if there's an error
        $conn->rollback();
        return "Error adding client and contacts: " . $e->getMessage();
    }
}

// Function to fetch all clients and their related contacts
function fetchAllClientsAndContacts() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SELECT query with JOIN
        $stmt = $pdo->query("
            SELECT c.*, ct.contactPersonName, ct.contactId,ct.contactNumber, ct.email
            FROM clients c
            LEFT JOIN contact ct ON c.clientId = ct.clientId
        ");

        // Initialize an array to store clients and their contacts
        $clientsAndContacts = array();

        // Fetch all client and contact records
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clientId = $row['clientId'];
            // If the client is not yet in the array, add it
            if (!isset($clientsAndContacts[$clientId])) {
                $clientsAndContacts[$clientId] = array(
                    'clientId' => $row['clientId'],
                    'clientName' => $row['clientName'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'gst' => $row['gst'],
                    'pan' => $row['pan'],
                    'contacts' => array()
                );
            }
            // If contact details exist, add them to the contacts array
            if ($row['contactPersonName'] !== null) {
                $clientsAndContacts[$clientId]['contacts'][] = array(
                    'contactId' => $row['contactId'],
                    'contactPersonName' => $row['contactPersonName'],
                    'contactNumber' => $row['contactNumber'],
                    'email' => $row['email']
                );
            }
        }

        return $clientsAndContacts; // Return all clients and contacts
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null;
    }
}

// function fetchAllClientsAndContactsNew() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Get parameters from request
//         $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
//         $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
//         $search = isset($_GET['search']) ? $_GET['search'] : '';

//         $offset = ($page - 1) * $pageSize;

//         // Base query
//         $query = "SELECT c.*, ct.contactPersonName, ct.contactId, ct.contactNumber, ct.email 
//                   FROM clients c
//                   LEFT JOIN contact ct ON c.clientId = ct.clientId 
//                   WHERE 1=1";

//         // Add search filter
//         if (!empty($search)) {
//             $query .= " AND (c.clientName LIKE :search OR c.address LIKE :search OR c.city LIKE :search OR c.state LIKE :search OR ct.contactPersonName LIKE :search)";
//         }

//         // Add pagination
//         $query .= " LIMIT :offset, :pageSize";

//         $stmt = $conn->prepare($query);

//         // Bind parameters
//         if (!empty($search)) {
//             $searchTerm = "%$search%";
//             $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

//         $stmt->execute();
//         $clientsAndContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Get total count for pagination
//         $countQuery = "SELECT COUNT(DISTINCT c.clientId) AS total FROM clients c LEFT JOIN contact ct ON c.clientId = ct.clientId WHERE 1=1";

//         if (!empty($search)) {
//             $countQuery .= " AND (c.clientName LIKE :search OR c.address LIKE :search OR c.city LIKE :search OR c.state LIKE :search OR ct.contactPersonName LIKE :search)";
//         }

//         $countStmt = $conn->prepare($countQuery);
//         if (!empty($search)) {
//             $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
//         }

//         $countStmt->execute();
//         $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         return [
//             'data' => $clientsAndContacts,
//             'total' => $totalCount,
//             'page' => $page,
//             'pageSize' => $pageSize
//         ];

//     } catch (PDOException $e) {
//         return json_encode(['error' => $e->getMessage()]);
//     }
// }

function fetchAllClientsAndContactsNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get parameters from request
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $offset = ($page - 1) * $pageSize;

        // Base query
        $query = "SELECT c.*, ct.contactPersonName, ct.contactId, ct.contactNumber, ct.email 
                  FROM clients c
                  LEFT JOIN contact ct ON c.clientId = ct.clientId 
                  WHERE 1=1";

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (c.clientName LIKE :search OR c.address LIKE :search OR c.city LIKE :search OR c.state LIKE :search OR ct.contactPersonName LIKE :search)";
        }

        // Add pagination
        $query .= " LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);

        // Bind parameters
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $clientsAndContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ---------- Fetch areas for each client ----------
        $clientIds = array_unique(array_column($clientsAndContacts, 'clientId'));
        if (!empty($clientIds)) {
            $inQuery = implode(',', $clientIds);
            $areaStmt = $conn->query("SELECT * FROM area WHERE clientId IN ($inQuery)");
            $areas = $areaStmt->fetchAll(PDO::FETCH_ASSOC);

            // Group areas by clientId
            $areasByClient = [];
            foreach ($areas as $area) {
                $areasByClient[$area['clientId']][] = $area;
            }

            // Add areas to each client record
            foreach ($clientsAndContacts as &$client) {
                $client['areas'] = $areasByClient[$client['clientId']] ?? [];
            }
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(DISTINCT c.clientId) AS total FROM clients c LEFT JOIN contact ct ON c.clientId = ct.clientId WHERE 1=1";
        if (!empty($search)) {
            $countQuery .= " AND (c.clientName LIKE :search OR c.address LIKE :search OR c.city LIKE :search OR c.state LIKE :search OR ct.contactPersonName LIKE :search)";
        }

        $countStmt = $conn->prepare($countQuery);
        if (!empty($search)) {
            $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data' => $clientsAndContacts,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];

    } catch (PDOException $e) {
        return json_encode(['error' => $e->getMessage()]);
    }
}

// function getAllClientsAndArea() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Base query to get clients with contacts
//         $query = "SELECT c.*, ct.contactPersonName, ct.contactId, ct.contactNumber, ct.email
//                   FROM clients c
//                   LEFT JOIN contact ct ON c.clientId = ct.clientId";

//         $stmt = $conn->prepare($query);
//         $stmt->execute();
//         $clientsAndContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Fetch areas for each client
//         $clientIds = array_unique(array_column($clientsAndContacts, 'clientId'));
//         if (!empty($clientIds)) {
//             $inQuery = implode(',', $clientIds);
//             $areaStmt = $conn->query("SELECT * FROM area WHERE clientId IN ($inQuery)");
//             $areas = $areaStmt->fetchAll(PDO::FETCH_ASSOC);

//             // Group areas by clientId
//             $areasByClient = [];
//             foreach ($areas as $area) {
//                 $areasByClient[$area['clientId']][] = $area;
//             }

//             // Add areas to each client record
//             foreach ($clientsAndContacts as &$client) {
//                 $client['areas'] = $areasByClient[$client['clientId']] ?? [];
//             }
//         }

//         return [
//             'data' => $clientsAndContacts
//         ];

//     } catch (PDOException $e) {
//         return json_encode(['error' => $e->getMessage()]);
//     }
// }

function getAllClientsAndArea() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 1) Fetch clients (only required columns)
        $clientsStmt = $conn->prepare("SELECT clientId, clientName, address -- add other needed columns here
                                       FROM clients");
        $clientsStmt->execute();
        $clients = $clientsStmt->fetchAll();

        if (empty($clients)) {
            return ['data' => []];
        }

        // build index by clientId and collect ids
        $clientIds = [];
        $clientsById = [];
        foreach ($clients as $c) {
            $cid = (int)$c['clientId'];
            $clientIds[] = $cid;
            $clientsById[$cid] = $c;
            $clientsById[$cid]['contacts'] = [];
            $clientsById[$cid]['areas'] = [];
        }

        // prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));

        // 2) Fetch contacts once for all clients
        $contactsSql = "SELECT contactId, clientId, contactPersonName, contactNumber, email
                        FROM contact
                        WHERE clientId IN ($placeholders)";
        $contactsStmt = $conn->prepare($contactsSql);
        $contactsStmt->execute($clientIds);
        $contacts = $contactsStmt->fetchAll();
        foreach ($contacts as $ct) {
            $cid = (int)$ct['clientId'];
            if (isset($clientsById[$cid])) {
                $clientsById[$cid]['contacts'][] = $ct;
            }
        }

        // 3) Fetch areas once for all clients
        $areasSql = "SELECT areaId, clientId, areaName -- add other area columns as needed
                     FROM area
                     WHERE clientId IN ($placeholders)";
        $areasStmt = $conn->prepare($areasSql);
        $areasStmt->execute($clientIds);
        $areas = $areasStmt->fetchAll();
        foreach ($areas as $a) {
            $cid = (int)$a['clientId'];
            if (isset($clientsById[$cid])) {
                $clientsById[$cid]['areas'][] = $a;
            }
        }

        // final numeric-indexed array
        $final = array_values($clientsById);

        return ['data' => $final];

    } catch (PDOException $e) {
        // return consistent error shape
        return ['error' => $e->getMessage()];
    }
}

// Function to fetch a client and their associated contacts by client ID
// function getClientAndContactsById($clientId) {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         // Connect to the database
//         $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Prepare and execute the SELECT query with JOIN
//         $stmt = $pdo->prepare("
//             SELECT c.*, ct.contactPersonName, ct.contactId, ct.contactNumber, ct.email
//             FROM clients c
//             LEFT JOIN contact ct ON c.clientId = ct.clientId
//             WHERE c.clientId = :clientId
//         ");
        
//         $stmt->bindParam(':clientId', $clientId);
//         $stmt->execute();
        
//         // Initialize variables to store client and contacts
//         $client = null;
//         $contacts = array();

//         // Fetch client and contact records
//         while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//             // If client details are not yet fetched, populate client object
//             if ($client === null) {
//                 $client = array(
//                     'clientId' => $row['clientId'],
//                     'clientName' => $row['clientName'],
//                     'address' => $row['address'],
//                     'city' => $row['city'],
//                     'state' => $row['state'],
//                     'gst' => $row['gst'],
//                     'pan' => $row['pan'],
//                     'contacts' => array()
//                 );
//             }
//             // If contact details exist, add them to the contacts array
//             if ($row['contactPersonName'] !== null) {
//                 $contacts[] = array(
//                     'contactPersonName' => $row['contactPersonName'],
//                     'contactNumber' => $row['contactNumber'],
//                     'email' => $row['email']
//                 );
//             }
//         }

//         // Combine client and contacts into a single object
//         $client['contacts'] = $contacts;

//         return $client; // Return client with associated contacts
//     } catch (PDOException $e) {
//         // Handle database connection or query errors
//         return null;
//     }
// }

// add areas
function getClientAndContactsById($clientId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SELECT query with JOIN for contacts
        $stmt = $pdo->prepare("
            SELECT c.*, ct.contactPersonName, ct.contactId, ct.contactNumber, ct.email
            FROM clients c
            LEFT JOIN contact ct ON c.clientId = ct.clientId
            WHERE c.clientId = :clientId
        ");
        
        $stmt->bindParam(':clientId', $clientId);
        $stmt->execute();
        
        // Initialize variables to store client and contacts
        $client = null;
        $contacts = array();

        // Fetch client and contact records
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($client === null) {
                $client = array(
                    'clientId' => $row['clientId'],
                    'clientName' => $row['clientName'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'gst' => $row['gst'],
                    'pan' => $row['pan'],
                    'contacts' => array(),
                    'areas' => array() // Add areas array here
                );
            }

            // Add contact if exists
            if ($row['contactPersonName'] !== null) {
                $contacts[] = array(
                    'contactPersonName' => $row['contactPersonName'],
                    'contactNumber' => $row['contactNumber'],
                    'email' => $row['email']
                );
            }
        }

        // Assign contacts
        $client['contacts'] = $contacts;

        // Fetch areas separately (keep current response format)
        $stmtAreas = $pdo->prepare("
            SELECT areaId, areaName
            FROM area
            WHERE clientId = :clientId
        ");
        $stmtAreas->bindParam(':clientId', $clientId);
        $stmtAreas->execute();
        $areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

        $client['areas'] = $areas; // Add areas to client object

        return $client;

    } catch (PDOException $e) {
        return null;
    }
}


// Function to delete a client and their associated contacts by ID
function deleteClientAndContacts($clientId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin a transaction
        $pdo->beginTransaction();

        // Delete client from clients table
        $stmtClient = $pdo->prepare("DELETE FROM clients WHERE clientId = :clientId");
        $stmtClient->bindParam(':clientId', $clientId);
        $stmtClient->execute();

        // Delete associated contacts from contact table
        $stmtContacts = $pdo->prepare("DELETE FROM contact WHERE clientId = :clientId");
        $stmtContacts->bindParam(':clientId', $clientId);
        $stmtContacts->execute();

        // Commit the transaction
        $pdo->commit();

        return true; // Return true on successful deletion
    } catch (PDOException $e) {
        // Rollback the transaction if an error occurs
        $pdo->rollback();
        return false; // Return false if deletion fails due to error
    }
}
// Function to update a client and its associated contacts
function updateClientAndContacts($clientId, $clientName, $address, $city, $state, $gst, $pan, $contacts, $areas) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Start a transaction
        $conn->beginTransaction();
        
        // Update client details
        $sql_client = "UPDATE clients SET clientName = :clientName, address = :address, city = :city, state = :state, gst = :gst, pan = :pan WHERE clientId = :clientId";
        $stmt_client = $conn->prepare($sql_client);
        $stmt_client->bindParam(':clientName', $clientName);
        $stmt_client->bindParam(':address', $address);
        $stmt_client->bindParam(':city', $city);
        $stmt_client->bindParam(':state', $state);
        $stmt_client->bindParam(':gst', $gst);
        $stmt_client->bindParam(':pan', $pan);
        $stmt_client->bindParam(':clientId', $clientId);
        $stmt_client->execute();

        // Fetch all existing contacts for the client
        $sql_existing_contacts = "SELECT contactId, contactPersonName, contactNumber FROM contact WHERE clientId = :clientId";
        $stmt_existing_contacts = $conn->prepare($sql_existing_contacts);
        $stmt_existing_contacts->bindParam(':clientId', $clientId);
        $stmt_existing_contacts->execute();
        $existingContacts = $stmt_existing_contacts->fetchAll(PDO::FETCH_ASSOC);

        // Create a map of the existing contacts by contactPersonName and contactNumber for quick lookup
        $existingContactMap = [];
        foreach ($existingContacts as $existingContact) {
            $existingContactMap[$existingContact['contactPersonName'] . '_' . $existingContact['contactNumber']] = $existingContact;
        }

        // Arrays to store IDs of contacts to be inserted and deleted
        $contactsToInsert = [];
        $contactsToKeep = [];
        $contactIdsToDelete = [];

        // Loop through the provided contacts
        foreach ($contacts as $contact) {
            $contactKey = $contact['contactPersonName'] . '_' . $contact['contactNumber'];

            if (isset($existingContactMap[$contactKey])) {
                // Contact exists, so we will keep it (no need to insert)
                $contactsToKeep[] = $existingContactMap[$contactKey]['contactId'];

                // Update the contact details if needed (optional)
                $sql_update_contact = "UPDATE contact SET email = :email WHERE contactId = :contactId";
                $stmt_update_contact = $conn->prepare($sql_update_contact);
                $stmt_update_contact->bindParam(':email', $contact['email']);
                $stmt_update_contact->bindParam(':contactId', $existingContactMap[$contactKey]['contactId']);
                $stmt_update_contact->execute();
            } else {
                // New contact, add it to the list of contacts to insert
                $contactsToInsert[] = $contact;
            }
        }

        // Check if the existing contacts to be deleted are used in quotation, assignable, or inquiry tables
        foreach ($existingContacts as $existingContact) {
            if (!in_array($existingContact['contactId'], $contactsToKeep)) {
                // Check if the contact is used in the quotation table
                $sql_check_quotation = "SELECT COUNT(*) FROM quotation WHERE contactName = :contactPersonName AND clientId = :clientId";
                $stmt_check_quotation = $conn->prepare($sql_check_quotation);
                $stmt_check_quotation->bindParam(':contactPersonName', $existingContact['contactPersonName']);
                $stmt_check_quotation->bindParam(':clientId', $clientId);
                $stmt_check_quotation->execute();
                $quotationCount = $stmt_check_quotation->fetchColumn();

                // Check if the contact is used in the assignable table
                $sql_check_assignable = "SELECT COUNT(*) FROM assignables WHERE contactName = :contactPersonName";
                $stmt_check_assignable = $conn->prepare($sql_check_assignable);
                $stmt_check_assignable->bindParam(':contactPersonName', $existingContact['contactPersonName']);
                $stmt_check_assignable->execute();
                $assignableCount = $stmt_check_assignable->fetchColumn();

                // Check if the contactId is used in the inquiry table
                $sql_check_inquiry = "SELECT COUNT(*) FROM inquiry WHERE contactId = :contactId AND clientId = :clientId";
                $stmt_check_inquiry = $conn->prepare($sql_check_inquiry);
                $stmt_check_inquiry->bindParam(':contactId', $existingContact['contactId']);
                $stmt_check_inquiry->bindParam(':clientId', $clientId);
                $stmt_check_inquiry->execute();
                $inquiryCount = $stmt_check_inquiry->fetchColumn();

                // If the contact is not used anywhere, mark it for deletion
                if ($quotationCount == 0 && $assignableCount == 0 && $inquiryCount == 0) {
                    $contactIdsToDelete[] = $existingContact['contactId'];
                }
            }
        }

        // Insert new contacts
        $sql_insert_contact = "INSERT INTO contact (contactPersonName, contactNumber, email, clientId) VALUES (:contactPersonName, :contactNumber, :email, :clientId)";
        $stmt_insert_contact = $conn->prepare($sql_insert_contact);

        foreach ($contactsToInsert as $contact) {
            $stmt_insert_contact->bindParam(':contactPersonName', $contact['contactPersonName']);
            $stmt_insert_contact->bindParam(':contactNumber', $contact['contactNumber']);
            $stmt_insert_contact->bindParam(':email', $contact['email']);
            $stmt_insert_contact->bindParam(':clientId', $clientId);
            $stmt_insert_contact->execute();
        }

        // Delete contacts that are not in the new list and are not used in other tables
        if (!empty($contactIdsToDelete)) {
            $contactIdsToDeleteStr = implode(',', $contactIdsToDelete);
            $sql_delete_contacts = "DELETE FROM contact WHERE clientId = :clientId AND contactId IN ($contactIdsToDeleteStr)";
            $stmt_delete_contacts = $conn->prepare($sql_delete_contacts);
            $stmt_delete_contacts->bindParam(':clientId', $clientId);
            $stmt_delete_contacts->execute();
        }
        
        // ---------- Area Update Logic ----------
        // Delete all existing areas for this client
        $stmt_delete_areas = $conn->prepare("DELETE FROM area WHERE clientId = :clientId");
        $stmt_delete_areas->bindParam(':clientId', $clientId);
        $stmt_delete_areas->execute();

        // Insert new/updated areas
        $stmt_area = $conn->prepare("INSERT INTO area (areaName, clientId) VALUES (:areaName, :clientId)");
        foreach ($areas as $a) {
            $stmt_area->execute([
                ':areaName' => $a['areaName'],
                ':clientId' => $clientId
            ]);
        }

        // Commit the transaction
        $conn->commit();
        return "Client, contacts & areas updated successfully";
    } catch (PDOException $e) {
        // Rollback the transaction if there's an error
        $conn->rollback();
        return "Error updating client and contacts: " . $e->getMessage();
    }
}

// Function to insert a quotation into the database
function insertQuotation($qutReference, $date, $subject, $clientId, $contactId, $item, $unit, $qty, $ratePerUnit, $scopeOfWork, $version, $createdBy) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dateTime = new DateTime();
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $currentDate = $dateTime->format('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO quotation (qutReference, date, subject, clientId, contactId, item, unit, qty, ratePerUnit, scopeOfWork, version, created_at, last_modified, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$qutReference, $date, $subject, $clientId, $contactId, $item, $unit, $qty, $ratePerUnit, $scopeOfWork, $version, $currentDate, $currentDate, $createdBy]);

        return true; // Return true on successful insertion
    } catch (PDOException $e) {
        // Handle database connection errors or insertion errors
        return false; // Return false if insertion fails
    }
}

// Function to fetch details of a quotation from the database
function fetchQuotationDetails($qutId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM quotation WHERE qutId = ?");
        $stmt->execute([$qutId]);
        $quotation = $stmt->fetch(PDO::FETCH_ASSOC);

        return $quotation; // Return quotation details
    } catch (PDOException $e) {
        // Handle database connection errors or query errors
        return null; // Return null if fetching fails
    }
}

// Function to update a quotation in the database
function updateQuotation($qutId, $qutReference, $date, $subject, $clientId, $contactId, $item, $unit, $qty, $ratePerUnit, $scopeOfWork, $version, $createdBy) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("UPDATE quotation SET qutReference = ?, date = ?, subject = ?, clientId = ?, contactId = ?, item = ?, unit = ?, qty = ?, ratePerUnit = ?, scopeOfWork = ?, version = ?, last_modified = NOW(), created_by = ? WHERE qutId = ?");
        $stmt->execute([$qutReference, $date, $subject, $clientId, $contactId, $item, $unit, $qty, $ratePerUnit, $scopeOfWork, $version, $createdBy, $qutId]);

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

// Function to fetch dashboard count
function fetchDashboardCount($teamName) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if($teamName == 'Master Admin')
        {
            $query = "SELECT COUNT(*) FROM clients";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllClientsCount = $stmt->fetchColumn();

            $query = "SELECT COUNT(*) FROM users";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllUsersCount =  $stmt->fetchColumn();

            $query = "SELECT COUNT(*) FROM quotation";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllQuotationsCount = $stmt->fetchColumn();
            
            $query = "SELECT COUNT(*) FROM inquiry";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllInquiryCount = $stmt->fetchColumn();
            
            $query = "SELECT status, COUNT(*) AS assignStatusCount FROM assignables GROUP BY status";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllAssignableCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
             // Construct the dashboardcount object
            $dashboardcountObject = array(
                'clientscount' => $AllClientsCount,
                'userscount' => $AllUsersCount,
                'quotationcount' => $AllQuotationsCount,
                'inquirycount' => $AllInquiryCount,
                'assignablecount'=>$AllAssignableCount
            );
        }elseif($teamName == 'Business Development')
        {
            $query = "SELECT COUNT(*) FROM clients";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllClientsCount = $stmt->fetchColumn();           

             $query = "SELECT COUNT(*) FROM quotation";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllQuotationsCount = $stmt->fetchColumn();
            
             $query = "SELECT COUNT(*) FROM inquiry";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllInquiryCount = $stmt->fetchColumn();
            
            $query = "SELECT status, COUNT(*) FROM assignables GROUP BY status;";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $AllAssignableCount = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dashboardcountObject = array(
                'clientscount' => $AllClientsCount,                
                'quotationcount' => $AllQuotationsCount,
                'inquirycount' => $AllInquiryCount,
                'assignablecount'=>$AllAssignableCount
            );

        }elseif(strpos($teamName, 'Execution') !== false || strpos($teamName, 'Account') !== false )
        {
            $query = "SELECT uA.assignStatus, COUNT(*) AS assignStatusCount FROM userAssignments uA LEFT JOIN teamConfiguration tC ON uA.teamId = tC.teamId WHERE tC.teamName = :teamName GROUP BY uA.assignStatus, tC.teamId";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':teamName', $teamName);
            $stmt->execute();
            $AllAssigntaskCount = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $dashboardcountObject = array(                
                'userAssignmentsCount' => $AllAssigntaskCount
            );
        }
        
         
        
        return $dashboardcountObject; // Return count of all clients,users,quotation,inquiry
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null;
    }
}

function insertNotificationForAll($notificationText, $status, $notificationDate, $notificationType, $configData, $addedByTeam) {
    global $host, $dbName, $dbUname, $dbPass;

    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Constants for team names
        $teamId = 5;
        $BD = 'Business Development';
        $Account = 'Account Team';
        $Execution = "Execution Team ";
        $MD = "Master Admin";
        
        // Parse the ISO 8601 date-time string, automatically adjusting for time zone
        $dateTime = new DateTime($notificationDate);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $notificationDate = $dateTime->format('Y-m-d H:i:s');

        $pdo->beginTransaction();
        // Insert notification for Master Admin if applicable
        if ($addedByTeam != $MD) {
            $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                      VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':notificationText', $notificationText);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notificationDate', $notificationDate);
            $stmt->bindParam(':teamName', $MD);
            $stmt->bindParam(':notificationType', $notificationType);
            $stmt->bindParam(':configData', $configData);
            $stmt->bindParam(':addedByTeam', $addedByTeam);
            $stmt->execute();
        }
        
        // Insert notification for Business Development if applicable
        if ($addedByTeam != $BD) {
            
            $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                      VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':notificationText', $notificationText);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notificationDate', $notificationDate);
            $stmt->bindParam(':teamName', $BD);
            $stmt->bindParam(':notificationType', $notificationType);
            $stmt->bindParam(':configData', $configData);
            $stmt->bindParam(':addedByTeam', $addedByTeam);
            $stmt->execute();
        }

        // Insert notifications based on addedByTeam being BD or MD
        if ($addedByTeam == $BD || $addedByTeam == $MD) {
            $checkQuery = "SELECT tC.teamName FROM userAssignments uA LEFT JOIN teamConfiguration tC ON uA.teamId = tC.teamId WHERE uA.assignableId = :assignableId AND uA.teamId != :teamId";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':assignableId', $configData);
            $checkStmt->bindParam(':teamId', $teamId);
            $checkStmt->execute();
            $teamNameFetched = $checkStmt->fetchColumn();
            
            $ExecutionTeam = $teamNameFetched;
            if ($teamNameFetched != null || $teamNameFetched != '') {
                $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                          VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':notificationText', $notificationText);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':notificationDate', $notificationDate);
                $stmt->bindParam(':teamName', $ExecutionTeam);
                $stmt->bindParam(':notificationType', $notificationType);
                $stmt->bindParam(':configData', $configData);
                $stmt->bindParam(':addedByTeam', $addedByTeam);
                $stmt->execute();
            }

            $checkQuery = "SELECT COUNT(*) FROM userAssignments WHERE assignableId = :assignableId AND teamId = :teamId";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':assignableId', $configData);
            $checkStmt->bindParam(':teamId', $teamId);
            $checkStmt->execute();
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                          VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':notificationText', $notificationText);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':notificationDate', $notificationDate);
                $stmt->bindParam(':teamName', $Account);
                $stmt->bindParam(':notificationType', $notificationType);
                $stmt->bindParam(':configData', $configData);
                $stmt->bindParam(':addedByTeam', $addedByTeam);
                $stmt->execute();
            }
        }

        // Insert notifications for Execution Team when added by Execution Team
        if (strpos($addedByTeam, 'Execution') !== false) {
            $checkQuery = "SELECT COUNT(*) FROM userAssignments WHERE assignableId = :assignableId AND teamId = :teamId";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':assignableId', $configData);
            $checkStmt->bindParam(':teamId', $teamId);
            $checkStmt->execute();
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                          VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':notificationText', $notificationText);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':notificationDate', $notificationDate);
                $stmt->bindParam(':teamName', $Account);
                $stmt->bindParam(':notificationType', $notificationType);
                $stmt->bindParam(':configData', $configData);
                $stmt->bindParam(':addedByTeam', $addedByTeam);
                $stmt->execute();
            }
        }

        // Insert notifications for Execution Team when added by Account Team
        if ($addedByTeam == $Account) {
            $checkQuery = "SELECT tC.teamName FROM userAssignments uA LEFT JOIN teamConfiguration tC ON uA.teamId = tC.teamId WHERE uA.assignableId = :assignableId AND uA.teamId != :teamId";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(':assignableId', $configData);
            $checkStmt->bindParam(':teamId', $teamId);
            $checkStmt->execute();
            $teamNameFetched = $checkStmt->fetchColumn();
            
            $ExecutionTeam = $teamNameFetched;
            if ($teamNameFetched != null || $teamNameFetched != '') {
                $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                          VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':notificationText', $notificationText);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':notificationDate', $notificationDate);
                $stmt->bindParam(':teamName', $ExecutionTeam);
                $stmt->bindParam(':notificationType', $notificationType);
                $stmt->bindParam(':configData', $configData);
                $stmt->bindParam(':addedByTeam', $addedByTeam);
                $stmt->execute();
            }
        }

        // Commit the transaction
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        // Rollback the transaction in case of error
        $pdo->rollBack();
        // Log the error message
        error_log($e->getMessage());
        return false;
    }
}

function insertNotification($teamName, $notificationText, $status, $notificationDate, $notificationType, $configData, $addedByTeam) {
    global $host, $dbName, $dbUname, $dbPass;


    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Parse the ISO 8601 date-time string, automatically adjusting for time zone
        $dateTime = new DateTime($notificationDate);
        // Set the desired time zone for storing in the database (IST)
        $dateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
        $notificationDate = $dateTime->format('Y-m-d H:i:s');
        
        $pdo->beginTransaction();
        $query = "INSERT INTO notifications (notificationText, status, notificationDate, teamName, notificationType, configData, addedByTeam)
                      VALUES (:notificationText, :status, :notificationDate, :teamName, :notificationType, :configData, :addedByTeam)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':notificationText', $notificationText);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notificationDate', $notificationDate);
            $stmt->bindParam(':teamName', $teamName);
            $stmt->bindParam(':notificationType', $notificationType);
            $stmt->bindParam(':configData', $configData);
            $stmt->bindParam(':addedByTeam', $addedByTeam);
            $stmt->execute();
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        // Rollback the transaction in case of error
        $pdo->rollBack();
        // Log the error message
        error_log($e->getMessage());
        return false;
    }
}

// Function to fetch notifications by team name
function fetchNotificationsByTeamName($teamName) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        $query = "SELECT * FROM notifications WHERE teamName = :teamName";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':teamName', $teamName);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $notifications;
    } catch (PDOException $e) {
        // Handle database connection or query errors
        return null;
    }
}

// Function to mark a notification as read
function markNotificationAsRead($notificationId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE notificationId = :notificationId");
        $stmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);

        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false;
    }
}

// Function to mark a notification as read
function markAllNotificationAsRead($teamName) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' WHERE teamName = :teamName");
        $stmt->bindParam(':teamName', $teamName, PDO::PARAM_INT);

        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        // Handle database connection errors or update errors
        return false;
    }
}

function markNotificationStatus($notificationId, $status) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($status !== 'read' && $status !== 'unread') {
            throw new Exception("Invalid status value");
        }

        $stmt = $pdo->prepare("UPDATE notifications SET status = :status WHERE notificationId = :notificationId");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':notificationId', $notificationId, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}



// Function to get user details from the database
function fetchMenuRoleMap() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM menuRoleMap");
        $stmt->execute();
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Organize data into role-based menu map
        $roleMenuMap = [];
        foreach ($rows as $row) {
            $role = $row['role'];
            $menuName = $row['allowedMenu'];
            if (!isset($roleMenuMap[$role])) {
                $roleMenuMap[$role] = [];
            }
            $roleMenuMap[$role][] = $menuName;
        }
        return $roleMenuMap;
    } catch (PDOException $e) {
        // Handle database connection errors
        return null;
    }
}

function addSignature($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            INSERT INTO signature (title, name, qualification, competentPersonUs, designation)
            VALUES (:title, :name, :qualification, :competentPersonUs, :designation)
        ");
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':qualification', $data['qualification']);
        $stmt->bindParam(':competentPersonUs', $data['competentPersonUs']); // ✅ correct column name
        $stmt->bindParam(':designation', $data['designation']);
        $stmt->execute();

        return ['success' => true, 'message' => 'Signature added successfully', 'id' => $conn->lastInsertId()];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateSignature($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            UPDATE signature
            SET title = :title,
                name = :name,
                qualification = :qualification,
                competentPersonUs = :competentPersonUs,
                designation = :designation
            WHERE signatureId = :signatureId
        ");
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':qualification', $data['qualification']);
        $stmt->bindParam(':competentPersonUs', $data['competentPersonUs']); // ✅ match DB
        $stmt->bindParam(':designation', $data['designation']);
        $stmt->bindParam(':signatureId', $data['signatureId'], PDO::PARAM_INT);
        $stmt->execute();

        return ['success' => true, 'message' => 'Signature updated successfully'];

    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


function fetchAllSignaturesNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $offset = ($page - 1) * $pageSize;

        $query = "SELECT * FROM signature WHERE 1=1";
        if (!empty($search)) {
            $query .= " AND (title LIKE :search OR name LIKE :search OR qualification LIKE :search OR competentUs LIKE :search OR designation LIKE :search)";
        }
        $query .= " LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countQuery = "SELECT COUNT(*) AS total FROM signature WHERE 1=1";
        if (!empty($search)) {
            $countQuery .= " AND (title LIKE :search OR name LIKE :search OR qualification LIKE :search OR competentUs LIKE :search OR designation LIKE :search)";
        }
        $countStmt = $conn->prepare($countQuery);
        if (!empty($search)) {
            $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data' => $signatures,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    } catch (PDOException $e) {
        return json_encode(['error' => $e->getMessage()]);
    }
}
function fetchAllSignatures() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all signatures
        $query = "SELECT * FROM signature";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $signatures
        ];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}


function getSignatureById($signatureId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM signature WHERE signatureId = :signatureId");
        $stmt->bindParam(':signatureId', $signatureId, PDO::PARAM_INT);
        $stmt->execute();
        $signature = $stmt->fetch(PDO::FETCH_ASSOC);

        return $signature ?: null;

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function deleteSignature($id) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM signature WHERE signatureId = :signatureId");
        $stmt->bindParam(':signatureId', $id, PDO::PARAM_INT); // <-- use $id
        $stmt->execute();

        return ['success' => true];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function fetchAllCalibrationsNew() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $offset = ($page - 1) * $pageSize;

        $query = "SELECT * FROM gauge_calibration WHERE 1=1";
        if (!empty($search)) {
            $query .= " AND (make LIKE :search OR rangeValue LIKE :search OR calibrationDoneBy LIKE :search)";
        }
        $query .= " ORDER BY srNo DESC LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $calibrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countQuery = "SELECT COUNT(*) AS total FROM gauge_calibration WHERE 1=1";
        if (!empty($search)) {
            $countQuery .= " AND (make LIKE :search OR rangeValue LIKE :search OR calibrationDoneBy LIKE :search)";
        }

        $countStmt = $conn->prepare($countQuery);
        if (!empty($search)) {
            $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data' => $calibrations,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function fetchAllCalibrations() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query to fetch all calibrations
        $query = "SELECT * FROM gauge_calibration ORDER BY srNo DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $calibrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $calibrations
        ];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}


// =================== FETCH BY SR NO ===================
function fetchCalibrationById($id) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM gauge_calibration WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// =================== ADD NEW CALIBRATION ===================
function addCalibration($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            INSERT INTO gauge_calibration (srNo,calibrationDate, make, calibrationDueDate, rangeValue, calibrationDoneBy)
            VALUES (:srNo, :calibrationDate, :make, :calibrationDueDate, :rangeValue, :calibrationDoneBy)
        ");

        $stmt->bindParam(':srNo', $data['srNo']);
        $stmt->bindParam(':calibrationDate', $data['calibrationDate']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':calibrationDueDate', $data['calibrationDueDate']);
        $stmt->bindParam(':rangeValue', $data['rangeValue']);
        $stmt->bindParam(':calibrationDoneBy', $data['calibrationDoneBy']);
        $stmt->execute();

        return ['status' => 'success'];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

// =================== UPDATE CALIBRATION ===================
function updateCalibration($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            UPDATE gauge_calibration 
            SET srNo = :srNo,
                calibrationDate = :calibrationDate,
                make = :make,
                calibrationDueDate = :calibrationDueDate,
                rangeValue = :rangeValue,
                calibrationDoneBy = :calibrationDoneBy
            WHERE id = :id
        ");

        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':srNo', $data['srNo']);  // string
        $stmt->bindParam(':calibrationDate', $data['calibrationDate']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':calibrationDueDate', $data['calibrationDueDate']);
        $stmt->bindParam(':rangeValue', $data['rangeValue']);
        $stmt->bindParam(':calibrationDoneBy', $data['calibrationDoneBy']);
        $stmt->execute();

        return ['status' => 'success'];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}


// =================== DELETE CALIBRATION ===================
function deleteCalibration($id) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM gauge_calibration WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return ['status' => 'deleted'];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function addMake($data) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $stmt = $conn->prepare("INSERT INTO make (name) VALUES (:name)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->execute();
        return ['status' => 'success'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function updateMake($data) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $stmt = $conn->prepare("UPDATE make SET name=:name WHERE id=:id");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':id', $data['id']);
        $stmt->execute();
        return ['status' => 'success'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function deleteMake($id) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $stmt = $conn->prepare("DELETE FROM make WHERE id=:id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return ['status' => 'success'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function fetchMakes() {
    global $host, $dbName, $dbUname, $dbPass;
    $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
    $stmt = $conn->query("SELECT * FROM make ORDER BY name");
    return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

// function fetchMakes() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         // Query to get all makes
//         $query = "SELECT * FROM make";
//         $stmt = $conn->prepare($query);
//         $stmt->execute();
//         $makes = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         return [
//             'data' => $makes
//         ];

//     } catch (PDOException $e) {
//         return ['error' => $e->getMessage()];
//     }
// }

function addReport($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
    INSERT INTO pressureGaugeReport (
        clientId, address, reportDate, area, type, tagNo, backPressure, make, size,
        temperature, testingMedium, tagNoCorrect, tagPlateCheck, gaugeId,
        setPressure1, setPressure2, setPressure3,
        resetPressure1, resetPressure2, resetPressure3,
        setPressureObservation, resetPressureObservation,
        observedLeakage, signatureId, reportNo, permissibleLeakage,shortClientCode,
        desiredSetPressure, desiredResetPressure
    ) VALUES (
        :clientId, :address, :reportDate, :area, :type, :tagNo, :backPressure, :make, :size,
        :temperature, :testingMedium, :tagNoCorrect, :tagPlateCheck, :gaugeId,
        :setPressure1, :setPressure2, :setPressure3,
        :resetPressure1, :resetPressure2, :resetPressure3,
        :setPressureObservation, :resetPressureObservation,
        :observedLeakage, :signatureId, :reportNo, :permissibleLeakage,:shortClientCode,
        :desiredSetPressure, :desiredResetPressure
    )
");

        // Bind parameters from the $data object
        $stmt->bindParam(':clientId', $data['clientId']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':reportDate', $data['reportDate']);
        $stmt->bindParam(':area', $data['area']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':tagNo', $data['tagNo']);
        $stmt->bindParam(':backPressure', $data['backPressure']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':size', $data['size']);
        // $stmt->bindParam(':temperature', $data['temperature']);
        $stmt->bindValue(':temperature', strval($data['temperature']), PDO::PARAM_STR);
        $stmt->bindParam(':testingMedium', $data['testingMedium']);
        $stmt->bindParam(':tagNoCorrect', $data['tagNoCorrect']);
        $stmt->bindParam(':tagPlateCheck', $data['tagPlateCheck']);
        $stmt->bindParam(':gaugeId', $data['gaugeId']);
        $stmt->bindParam(':setPressure1', $data['setPressure1']);
        $stmt->bindParam(':setPressure2', $data['setPressure2']);
        $stmt->bindParam(':setPressure3', $data['setPressure3']);
        $stmt->bindParam(':resetPressure1', $data['resetPressure1']);
        $stmt->bindParam(':resetPressure2', $data['resetPressure2']);
        $stmt->bindParam(':resetPressure3', $data['resetPressure3']);
        $stmt->bindParam(':observedLeakage', $data['observedLeakage']);
        $stmt->bindParam(':signatureId', $data['signatureId']);
        $stmt->bindParam(':reportNo', $data['reportNo']);
        $stmt->bindParam(':permissibleLeakage', $data['permissibleLeakage']);
        $stmt->bindParam(':setPressureObservation', $data['setPressureObservation']);
        $stmt->bindParam(':resetPressureObservation', $data['resetPressureObservation']);
        $stmt->bindParam(':shortClientCode', $data['shortClientCode']);
        $stmt->bindParam(':desiredSetPressure', $data['desiredSetPressure']);
        $stmt->bindParam(':desiredResetPressure', $data['desiredResetPressure']);
        
        $stmt->execute();

        return ['status' => 'success', 'id' => $conn->lastInsertId()];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function getNextReportNumber() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Match frontend's format: "NOV-2025"
        $currentMonth = strtoupper(date('M')); // e.g. "NOV"
        $currentYear = date('Y');

        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(reportNo, '/', -1) AS UNSIGNED)) AS max_no
            FROM pressureGaugeReport
            WHERE reportNo LIKE :month_year
        ");
        $stmt->execute([':month_year' => "%{$currentMonth}-{$currentYear}/%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $maxNo = $result['max_no'] ?? 0;
        $nextNo = str_pad($maxNo + 1, 3, '0', STR_PAD_LEFT);

        return $nextNo;
    } catch (PDOException $e) {
        return '001'; // fallback if DB fails
    }
}

function updateReport($data) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            UPDATE pressureGaugeReport SET
                clientId = :clientId,
                address = :address,
                reportDate = :reportDate,
                area = :area,
                type = :type,
                tagNo = :tagNo,
                backPressure = :backPressure,
                make = :make,
                size = :size,
                temperature = :temperature,
                testingMedium = :testingMedium,
                tagNoCorrect = :tagNoCorrect,
                tagPlateCheck = :tagPlateCheck,
                gaugeId = :gaugeId,
                setPressure1 = :setPressure1,
                setPressure2 = :setPressure2,
                setPressure3 = :setPressure3,
                resetPressure1 = :resetPressure1,
                resetPressure2 = :resetPressure2,
                resetPressure3 = :resetPressure3,
                setPressureObservation = :setPressureObservation,
                resetPressureObservation = :resetPressureObservation,
                observedLeakage = :observedLeakage,
                signatureId = :signatureId,
                reportNo = :reportNo,
                permissibleLeakage = :permissibleLeakage,
                shortClientCode = :shortClientCode,
                desiredSetPressure = :desiredSetPressure,
                desiredResetPressure = :desiredResetPressure
            WHERE id = :id
        ");

        // Bind parameters
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':clientId', $data['clientId']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':reportDate', $data['reportDate']);
        $stmt->bindParam(':area', $data['area']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':tagNo', $data['tagNo']);
        $stmt->bindParam(':backPressure', $data['backPressure']);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':size', $data['size']);
        // $stmt->bindParam(':temperature', $data['temperature']);
        $stmt->bindValue(':temperature', strval($data['temperature']), PDO::PARAM_STR);
        $stmt->bindParam(':testingMedium', $data['testingMedium']);
        $stmt->bindParam(':tagNoCorrect', $data['tagNoCorrect']);
        $stmt->bindParam(':tagPlateCheck', $data['tagPlateCheck']);
        $stmt->bindParam(':gaugeId', $data['gaugeId']);
        $stmt->bindParam(':setPressure1', $data['setPressure1']);
        $stmt->bindParam(':setPressure2', $data['setPressure2']);
        $stmt->bindParam(':setPressure3', $data['setPressure3']);
        $stmt->bindParam(':resetPressure1', $data['resetPressure1']);
        $stmt->bindParam(':resetPressure2', $data['resetPressure2']);
        $stmt->bindParam(':resetPressure3', $data['resetPressure3']);
        $stmt->bindParam(':setPressureObservation', $data['setPressureObservation']);
        $stmt->bindParam(':resetPressureObservation', $data['resetPressureObservation']);
        $stmt->bindParam(':observedLeakage', $data['observedLeakage']);
        $stmt->bindParam(':signatureId', $data['signatureId']);
        $stmt->bindParam(':reportNo', $data['reportNo']);
        $stmt->bindParam(':permissibleLeakage', $data['permissibleLeakage']);
        $stmt->bindParam(':shortClientCode', $data['shortClientCode']);
        $stmt->bindParam(':desiredSetPressure', $data['desiredSetPressure']);
        $stmt->bindParam(':desiredResetPressure', $data['desiredResetPressure']);

        $stmt->execute();
        return ['status' => 'success'];

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// function fetchAllPressureGaugeReports() {
//     global $host, $dbName, $dbUname, $dbPass;

//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
//         $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
//         $search = isset($_GET['search']) ? $_GET['search'] : '';
//         $offset = ($page - 1) * $pageSize;

//         // ✅ Main Query
//         $query = "
//             SELECT 
//                 r.id AS reportId,
//                 r.*,
//                 c.clientName,
//                 a.areaName,
//                 g.srNo AS gauge_srNo,
//                 g.make AS gauge_make,
//                 g.rangeValue AS gauge_rangeValue,
//                 g.calibrationDate AS gauge_calibrationDate,
//                 g.calibrationDueDate AS gauge_calibrationDueDate,
//                 g.calibrationDoneBy AS gauge_calibrationDoneBy,
//                 s.name AS signature_name,
//                 s.qualification AS signature_qualifications,
//                 s.competentPersonUs AS signature_competentPersonUs,
//                 s.designation AS signature_designation,
//                 m.name AS valveMake,
//                 DATE_SUB(DATE_ADD(r.reportDate, INTERVAL 1 YEAR), INTERVAL 1 DAY) AS reportDueDate
//             FROM pressureGaugeReport r
//             LEFT JOIN clients c ON r.clientId = c.clientId
//              LEFT JOIN area a ON r.area = a.areaId
//             LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
//             LEFT JOIN signature s ON r.signatureId = s.signatureId
//             LEFT JOIN make m ON r.make = m.id
//             WHERE 1=1
//         ";

//         if (!empty($search)) {
//             $query .= "
//                 AND (
//                     c.clientName LIKE :search OR 
//                     a.areaName LIKE :search OR 
//                     g.make LIKE :search OR 
//                     g.rangeValue LIKE :search OR 
//                     g.calibrationDoneBy LIKE :search OR
//                     s.name LIKE :search OR
//                     m.name LIKE :search
//                 )
//             ";
//         }

//         $query .= " ORDER BY r.id DESC LIMIT :offset, :pageSize";

//         $stmt = $conn->prepare($query);

//         if (!empty($search)) {
//             $searchTerm = "%$search%";
//             $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
//         }
//         $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
//         $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

//         $stmt->execute();
//         $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // ✅ Count Query
//         $countQuery = "
//             SELECT COUNT(*) AS total
//             FROM pressureGaugeReport r
//             LEFT JOIN clients c ON r.clientId = c.clientId
//             LEFT JOIN area a ON r.area = a.areaId
//             LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
//             LEFT JOIN signature s ON r.signatureId = s.signatureId
//             LEFT JOIN make m ON r.make = m.id
//             WHERE 1=1
//         ";

//         if (!empty($search)) {
//             $countQuery .= "
//                 AND (
//                     c.clientName LIKE :search OR 
//                     a.areaName LIKE :search OR 
//                     g.make LIKE :search OR 
//                     g.rangeValue LIKE :search OR 
//                     g.calibrationDoneBy LIKE :search OR
//                     s.name LIKE :search OR
//                     m.name LIKE :search
//                 )
//             ";
//         }

//         $countStmt = $conn->prepare($countQuery);
//         if (!empty($search)) {
//             $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
//         }

//         $countStmt->execute();
//         $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         return [
//             'data' => $reports,
//             'total' => $totalCount,
//             'page' => $page,
//             'pageSize' => $pageSize
//         ];

//     } catch (PDOException $e) {
//         return ['error' => $e->getMessage()];
//     }
// }

function fetchAllPressureGaugeReports() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $offset = ($page - 1) * $pageSize;

        // ✅ Main Query
        $query = "
            SELECT 
                r.id AS reportId,
                r.*,
                c.clientName,
                g.srNo AS gauge_srNo,
                g.make AS gauge_make,
                g.rangeValue AS gauge_rangeValue,
                g.calibrationDate AS gauge_calibrationDate,
                g.calibrationDueDate AS gauge_calibrationDueDate,
                g.calibrationDoneBy AS gauge_calibrationDoneBy,
                s.name AS signature_name,
                s.qualification AS signature_qualifications,
                s.competentPersonUs AS signature_competentPersonUs,
                s.designation AS signature_designation,
                m.name AS valveMake,
                DATE_SUB(DATE_ADD(r.reportDate, INTERVAL 1 YEAR), INTERVAL 1 DAY) AS reportDueDate
            FROM pressureGaugeReport r
            LEFT JOIN clients c ON r.clientId = c.clientId
            LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
            LEFT JOIN signature s ON r.signatureId = s.signatureId
            LEFT JOIN make m ON r.make = m.id
            WHERE 1=1
        ";

        if (!empty($search)) {
            $query .= "
                AND (
                    c.clientName LIKE :search OR 
                    r.area LIKE :search OR
                    r.tagNo LIKE :search OR
                    r.reportNo LIKE :search OR
                    r.gaugeId LIKE :search OR
                    r.type LIKE :search OR
                    g.make LIKE :search OR 
                    g.rangeValue LIKE :search OR 
                    g.calibrationDoneBy LIKE :search OR
                    s.name LIKE :search OR
                    m.name LIKE :search
                )
            ";
        }

        $query .= " ORDER BY r.id DESC LIMIT :offset, :pageSize";

        $stmt = $conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);

        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Count Query
        $countQuery = "
            SELECT COUNT(*) AS total
            FROM pressureGaugeReport r
            LEFT JOIN clients c ON r.clientId = c.clientId
            LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
            LEFT JOIN signature s ON r.signatureId = s.signatureId
            LEFT JOIN make m ON r.make = m.id
            WHERE 1=1
        ";

        if (!empty($search)) {
            $countQuery .= "
                AND (
                    c.clientName LIKE :search OR 
                    r.area LIKE :search OR
                    r.tagNo LIKE :search OR
                    r.reportNo LIKE :search OR
                    r.gaugeId LIKE :search OR
                    r.type LIKE :search OR
                    g.make LIKE :search OR 
                    g.rangeValue LIKE :search OR 
                    g.calibrationDoneBy LIKE :search OR
                    s.name LIKE :search OR
                    m.name LIKE :search
                )
            ";
        }

        $countStmt = $conn->prepare($countQuery);
        if (!empty($search)) {
            $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'data' => $reports,
            'total' => $totalCount,
            'page' => $page,
            'pageSize' => $pageSize
        ];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

function exportPressureGaugeReports() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
        $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

        $query = "
            SELECT 
                r.id AS reportId,
                r.*,
                c.clientName,
                g.make AS gauge_make,
                g.rangeValue AS gauge_rangeValue,
                g.calibrationDate AS gauge_calibrationDate,
                g.calibrationDoneBy AS gauge_calibrationDoneBy,
                s.name AS signature_name,
                m.name AS valveMake,
                DATE_SUB(DATE_ADD(r.reportDate, INTERVAL 1 YEAR), INTERVAL 1 DAY) AS reportDueDate
            FROM pressureGaugeReport r
            LEFT JOIN clients c ON r.clientId = c.clientId
            LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
            LEFT JOIN signature s ON r.signatureId = s.signatureId
             LEFT JOIN make m ON r.make = m.id
            WHERE 1=1
        ";

        if (!empty($search)) {
            $query .= "
                AND (
                    c.clientName LIKE :search OR 
                    r.area LIKE :search OR
                    r.tagNo LIKE :search OR
                    r.reportNo LIKE :search OR
                    r.type LIKE :search OR
                    g.make LIKE :search OR 
                    g.rangeValue LIKE :search OR 
                    g.calibrationDoneBy LIKE :search OR
                    s.name LIKE :search OR
                    m.name LIKE :search
                )
            ";
        }
        
        // ✅ DATE FILTER (MAIN FIX)
        if (!empty($startDate) && !empty($endDate)) {
            $query .= " AND DATE(r.reportDate) BETWEEN :startDate AND :endDate ";
        }

        $query .= " ORDER BY r.id DESC";

        $stmt = $conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        
        // ✅ Bind dates
        if (!empty($startDate) && !empty($endDate)) {
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
        }

        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'data' => $reports
        ];

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function getReportById($id) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "
            SELECT 
                r.id AS reportId,
                r.*,
                c.clientName,
                a.areaName,
                g.srNo AS gauge_srNo,
                g.make AS gauge_make,
                g.rangeValue AS gauge_rangeValue,
                g.calibrationDate AS gauge_calibrationDate,
                g.calibrationDueDate AS gauge_calibrationDueDate,
                g.calibrationDoneBy AS gauge_calibrationDoneBy,
                s.name AS signature_name,
                s.qualification AS signature_qualifications,
                s.competentPersonUs AS signature_competentPersonUs,
                s.designation AS signature_designation,
                m.name AS valveMake,
                DATE_SUB(DATE_ADD(r.reportDate, INTERVAL 1 YEAR), INTERVAL 1 DAY) AS reportDueDate
            FROM pressureGaugeReport r
            LEFT JOIN clients c ON r.clientId = c.clientId
            LEFT JOIN area a ON r.area = a.areaId
            LEFT JOIN gauge_calibration g ON r.gaugeId = g.id
            LEFT JOIN signature s ON r.signatureId = s.signatureId
            LEFT JOIN make m ON r.make = m.id
            WHERE r.id = :id
            LIMIT 1
        ";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($report) {
            return ['status' => 'success', 'data' => $report];
        } else {
            return ['status' => 'error', 'message' => 'Report not found'];
        }

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function deletePressureGaugeReport($id) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM pressureGaugeReport WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return ['status' => 'deleted'];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

?>
