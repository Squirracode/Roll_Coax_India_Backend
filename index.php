<?php
require_once 'functions.php';
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');  
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
if ($endpoint === 'getUser') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, fetch user details
        $username = $_GET['username'] ?? null;
        if ($username) {
            $userDetails = getUserDetails($username);
            if ($userDetails) {
                // Return JSON response with user details
                header('Content-Type: application/json');
                echo json_encode($userDetails);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Username is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'insertUser') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new user
        $data = json_decode(file_get_contents('php://input'), true);

        $deviceToken = $data['device_token'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $phone = $data['phone'] ?? '';
        $role = $data['role'] ?? '';
        // Assuming dateRegistered and lastLogin are provided in the correct format
        $dateRegistered = $data['date_registered'] ?? '';
        $lastLogin = $data['last_login'] ?? '';

        // Check if required fields are present
        if ($deviceToken && $username && $password && $role && $dateRegistered && $lastLogin) {
            $inserted = insertUser($deviceToken, $username, $password, $phone, $role, $dateRegistered, $lastLogin);
            if ($inserted) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "User inserted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to insert user"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateUser') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, update the user
        $data = json_decode(file_get_contents('php://input'), true);

        $Id = $data['id'] ?? 0;
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $email = $data['email'] ?? '';
        $role = $data['role'] ?? '';
        $name = $data['name'] ?? '';

        // Check if required fields are present
        if ($Id) {
            $updated = updateUser($Id, $username, $password, $email, $role, $name);
            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "User updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update user"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'addClient') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new client and contacts
        $data = json_decode(file_get_contents('php://input'), true);

        $clientName = $data['clientName'] ?? '';
        $address = $data['address'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $gst = $data['gst'] ?? '';
        $pan = $data['pan'] ?? '';
        $contacts = $data['contacts'] ?? array();
        $areas = $data['areas'] ?? array();
        
        // Check if required fields are present
        if ($clientName && $address && $city && $state && !empty($contacts)) {
            $result = addClientAndContacts($clientName, $address, $city, $state, $gst, $pan, $contacts,$areas);
            // Return success or error response
            header('Content-Type: application/json');
            echo json_encode(array("message" => $result));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'addUser') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new client and contacts
        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        $role = $data['role'] ?? '';
        
        // Check if required fields are present
        if ($username && $password && $email && $name && $role) {
            $result = addUser($username,$password,$email,$name,$role);
            // Return success or error response
            header('Content-Type: application/json');
            echo json_encode(array("message" => $result));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'getAllUsers') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $allUsers = getAllUsers();
        if ($allUsers) {
                // Return JSON response with client and contacts
                header('Content-Type: application/json');
                echo json_encode($allUsers);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Users not found"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'getUserById') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $userId = $_GET['userId'] ?? null;
        $allUsers = getUserById($userId);
        if ($allUsers) {
                // Return JSON response with client and contacts
                header('Content-Type: application/json');
                echo json_encode($allUsers);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Users not found"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchClients') {
    // Fetch all clients
    $allClients = fetchAllClientsAndContacts();
    // Return JSON response with clients
    header('Content-Type: application/json');
    echo json_encode($allClients);
}
elseif ($endpoint === 'fetchClientsNew') {
    // Fetch all clients
    $allClients = fetchAllClientsAndContactsNew();
    // Return JSON response with clients
    header('Content-Type: application/json');
    echo json_encode($allClients);
}
elseif ($endpoint === 'getAllClientsAndArea') {
    // Fetch all clients
    $allClients = getAllClientsAndArea();
    // Return JSON response with clients
    header('Content-Type: application/json');
    echo json_encode($allClients);
}
elseif ($endpoint === 'getClientAndContactsById') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, retrieve client and contacts by client ID
        $clientId = $_GET['clientId'] ?? null;

        if ($clientId) {
            $clientAndContacts = getClientAndContactsById($clientId);

            if ($clientAndContacts) {
                // Return JSON response with client and contacts
                header('Content-Type: application/json');
                echo json_encode($clientAndContacts);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Client or contacts not found"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Client ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'deleteClient') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, delete the client
        $clientId = $_GET['clientId'] ?? null;

        if ($clientId) {
            $deleted = deleteClientAndContacts($clientId);

            if ($deleted) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Client deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete client"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Client ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateClient') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, update the client
        $data = json_decode(file_get_contents('php://input'), true);

        $clientId = $data['clientId'] ?? '';
        $clientName = $data['clientName'] ?? '';
        $address = $data['address'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $gst = $data['gst'] ?? '';
        $pan = $data['pan'] ?? '';
        $contacts = $data['contacts'] ?? array();
        $areas = $data['areas'] ?? array();
        // Check if required fields are present
        if ($clientId && $clientName && $address && $city && $state && !empty($contacts)) {
            $result = updateClientAndContacts($clientId, $clientName, $address, $city, $state, $gst, $pan, $contacts,$areas);
            // Return success or error response
            header('Content-Type: application/json');
            echo json_encode(array("message" => $result));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchDashboardCount') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, update the client

        $loggedUser = $_GET['loggedUser'] ?? '';
        
        // Check if required fields are present
        if ($loggedUser) {
            $result = fetchDashboardCount($loggedUser);
            // Return success or error response
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'insertNotificationForAll') {
    $data = json_decode(file_get_contents('php://input'), true);

    $notificationText = $data['notificationText'] ?? '';
    $status = $data['status'] ?? 'unread';
    $addedByTeam = $data['addedByTeam'] ?? '';
    $notificationType = $data['notificationType'] ?? '';
    $configData = $data['configData'] ?? '';
    $notificationDate = $data['notificationDate'] ?? null;
    // Check if required fields are present
    if ($addedByTeam) {
        // Call the function to insert the notification
        if (insertNotificationForAll($notificationText, $status, $notificationDate, $notificationType, $configData, $addedByTeam)) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Notification inserted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert notification"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
    }
}elseif ($endpoint === 'insertNotification') {
    $data = json_decode(file_get_contents('php://input'), true);

    $notificationText = $data['notificationText'] ?? '';
    $status = $data['status'] ?? 'unread';
    $addedByTeam = $data['addedByTeam'] ?? '';
    $notificationType = $data['notificationType'] ?? '';
    $configData = $data['configData'] ?? '';
    $notificationDate = $data['notificationDate'] ?? null;
    $teamName = $data['teamName'] ?? '';
    // Check if required fields are present
    if ($addedByTeam && $teamName) {
        // Call the function to insert the notification
        if (insertNotification($teamName, $notificationText, $status, $notificationDate, $notificationType, $configData, $addedByTeam)) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Notification inserted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert notification"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required fields"));
    }
}elseif ($endpoint === 'fetchNotificationByTeamName') {
    $teamName = $_GET['teamName'] ?? '';
    if ($teamName) {
        // Call the function to fetch notifications by team name
        $notifications = fetchNotificationsByTeamName($teamName);
        if ($notifications !== null) {
            header('Content-Type: application/json');
            echo json_encode($notifications);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to fetch notifications"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Team name is required"));
    }
}elseif ($endpoint === 'markNotificationAsRead') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = $data['notificationId'] ?? '';

    if ($notificationId) {
        // Call the function to mark the notification as read
        $result = markNotificationAsRead($notificationId);
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Notification marked as read successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to mark notification as read"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Notification ID is required"));
    }
}elseif ($endpoint === 'markAllNotificationAsRead') {
    $data = json_decode(file_get_contents('php://input'), true);
    $teamName = $data['teamName'] ?? '';

    if ($teamName) {
        // Call the function to mark the notification as read
        $result = markAllNotificationAsRead($teamName);
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Notification all marked as read successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to mark all notification as read"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Team Name is required"));
    }
}elseif ($endpoint === 'markNotificationStatus') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notificationId = $data['notificationId'] ?? '';
    $status = $data['status'] ?? '';

    if ($notificationId && $status) {
        // Call the function to mark this specific notification
        $result = markNotificationStatus($notificationId, $status);
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(array(
                "success" => true,
                "message" => "Notification updated successfully",
                "notificationId" => $notificationId,
                "status" => $status
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Failed to update notification"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "NotificationId and Status are required"));
    }
}
elseif ($endpoint === 'addSignature') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = addSignature($input);
    header('Content-Type: application/json');
    echo json_encode($result);
}

elseif ($endpoint === 'updateSignature') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = updateSignature($input);
    header('Content-Type: application/json');
    echo json_encode($result);
}
elseif ($endpoint === 'fetchSignaturesNew') {
    $allSignatures = fetchAllSignaturesNew();
    header('Content-Type: application/json');
    echo json_encode($allSignatures);
}
elseif ($endpoint === 'fetchAllSignatures') {
    $allSignatures = fetchAllSignatures();
    header('Content-Type: application/json');
    echo json_encode($allSignatures);
}

elseif ($endpoint === 'getSignatureById') {
    $id = isset($_GET['signatureId']) ? (int)$_GET['signatureId'] : 0;
    $signature = getSignatureById($id);
    header('Content-Type: application/json');
    echo json_encode($signature);
}
elseif ($endpoint === 'deleteSignature') {
    $id = isset($_GET['signatureId']) ? (int)$_GET['signatureId'] : 0;
    $result = deleteSignature($id);
    header('Content-Type: application/json');
    echo json_encode($result);
}
elseif ($endpoint === 'fetchCalibrations') {
    $data = fetchAllCalibrationsNew();
    header('Content-Type: application/json');
    echo json_encode($data);
}
elseif ($endpoint === 'fetchAllCalibrations') {
    $allCalibrations = fetchAllCalibrations();
    header('Content-Type: application/json');
    echo json_encode($allCalibrations);
}


elseif ($endpoint === 'getCalibrationById') {
    $id = $_GET['id'] ?? 0;
    $data = fetchCalibrationById($id);
    header('Content-Type: application/json');
    echo json_encode($data);
}

elseif ($endpoint === 'addCalibration') {
    $input = json_decode(file_get_contents('php://input'), true);
    $data = addCalibration($input);
    header('Content-Type: application/json');
    echo json_encode($data);
}

elseif ($endpoint === 'updateCalibration') {
    $input = json_decode(file_get_contents('php://input'), true);
    $data = updateCalibration($input);
    header('Content-Type: application/json');
    echo json_encode($data);
}

elseif ($endpoint === 'deleteCalibration') {
    $id = $_GET['id'] ?? 0;
    $data = deleteCalibration($id);
    header('Content-Type: application/json');
    echo json_encode($data);
}
elseif ($endpoint === 'addMake') {
    $postData = json_decode(file_get_contents('php://input'), true);
    echo json_encode(addMake($postData));
}

elseif ($endpoint === 'updateMake') {
    $postData = json_decode(file_get_contents('php://input'), true);
    echo json_encode(updateMake($postData));
}

elseif ($endpoint === 'deleteMake') {
    $id = $_GET['id'] ?? null;
    echo json_encode(deleteMake($id));
}
elseif ($endpoint === 'fetchMakes') {
    // Fetch all makes
    $allMakes = fetchMakes();
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($allMakes);
}

elseif ($endpoint === 'fetchAllCalibrations') {
    $allCalibrations = fetchAllCalibrations();
    header('Content-Type: application/json');
    echo json_encode($allCalibrations);
}

elseif ($endpoint === 'addReport') {
    // Get the posted data.
    $postData = json_decode(file_get_contents('php://input'), true);
    
    // Call the function to add the report.
    $result = addReport($postData);
    
    // Send the response.
    header('Content-Type: application/json');
    echo json_encode($result);
}

elseif ($endpoint === 'getNextReportNumber') {
    $nextNo = getNextReportNumber();
    header('Content-Type: application/json');
    echo json_encode(['nextReportNo' => $nextNo]);
}


elseif ($endpoint === 'updateReport') {
    // Get the posted data.
    $postData = json_decode(file_get_contents('php://input'), true);
    
    // Call the function to update the report.
    $result = updateReport($postData);
    
    // Send the response.
    header('Content-Type: application/json');
    echo json_encode($result);
}

elseif ($endpoint === 'fetchAllPressureGaugeReports') {
    $allCalibrations = fetchAllPressureGaugeReports();
    header('Content-Type: application/json');
    echo json_encode($allCalibrations);
}
elseif ($endpoint === 'exportPressureGaugeReports') {
    $allCalibrations = exportPressureGaugeReports();
    header('Content-Type: application/json');
    echo json_encode($allCalibrations);
}

elseif ($endpoint === 'getReportById') {
    $id = $_GET['id'] ?? 0;
    $data = getReportById($id); // call the function we just created
    header('Content-Type: application/json');
    echo json_encode($data);
}

elseif ($endpoint === 'deletePressureGaugeReport') {
    $id = $_GET['id'] ?? 0;
    $data = deletePressureGaugeReport($id);
    header('Content-Type: application/json');
    echo json_encode($data);
}

elseif ($endpoint === 'fetchMenuRoleMap') {
// Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
            $menuRoleMap = fetchMenuRoleMap();
            if ($menuRoleMap !== null) {
                header('Content-Type: application/json');
                echo json_encode($menuRoleMap);
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to fetch menuRoleMap"));
            }
        }
    }

elseif ($endpoint === 'getRolesFromMenuRoleMap') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $roles = getRolesFromMenuRoleMap();
        header('Content-Type: application/json');
        echo json_encode($roles !== null ? $roles : []);
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}

elseif ($endpoint === 'getBulkReportsData') {
    $clientIds = isset($_GET['clientIds']) ? $_GET['clientIds'] : '';
    $data = getBulkReportsData($clientIds);
    header('Content-Type: application/json');
    echo json_encode($data);
}
else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
    
    
?>
