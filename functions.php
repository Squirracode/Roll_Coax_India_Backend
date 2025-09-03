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
function addClientAndContacts($clientName, $address, $city, $state, $gst, $pan, $contacts) {
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

        // Commit the transaction if all inserts are successful
        $conn->commit();
        return "Client and contacts added successfully";
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

// Function to fetch a client and their associated contacts by client ID
function getClientAndContactsById($clientId) {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SELECT query with JOIN
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
            // If client details are not yet fetched, populate client object
            if ($client === null) {
                $client = array(
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
                $contacts[] = array(
                    'contactPersonName' => $row['contactPersonName'],
                    'contactNumber' => $row['contactNumber'],
                    'email' => $row['email']
                );
            }
        }

        // Combine client and contacts into a single object
        $client['contacts'] = $contacts;

        return $client; // Return client with associated contacts
    } catch (PDOException $e) {
        // Handle database connection or query errors
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
function updateClientAndContacts($clientId, $clientName, $address, $city, $state, $gst, $pan, $contacts) {
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

        // Commit the transaction
        $conn->commit();
        return "Client and contacts updated successfully";
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

?>
