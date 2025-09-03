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
        
        // Check if required fields are present
        if ($clientName && $address && $city && $state && !empty($contacts)) {
            $result = addClientAndContacts($clientName, $address, $city, $state, $gst, $pan, $contacts);
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
        // Check if required fields are present
        if ($clientId && $clientName && $address && $city && $state && !empty($contacts)) {
            $result = updateClientAndContacts($clientId, $clientName, $address, $city, $state, $gst, $pan, $contacts);
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
}elseif ($endpoint === 'fetchMenuRoleMap') {
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
    
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
?>
