<?php
require_once 'qrcodeFunctions.php';
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
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

if ($endpoint === 'insertNewQr') {
    $authenticated = authenticateUser();
        
    if ($authenticated) {
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $qrData = array(
            'qrUniqueKey' => $data['qrUniqueKey'] ?? '',
            'clientId' => $data['clientId'] ?? '',
            'departmentName' => $data['departmentName'] ?? '',
            'contractorName' => $data['contractorName'] ?? '',
            'serialNumber' => $data['serialNumber'] ?? '',
            'swl' => $data['swl'] ?? '',
            'swp' => $data['swp'] ?? '',
            'certificateIssueDate' => $data['certificateIssueDate'] ?? '',
            'certificateExpDate' => $data['certificateExpDate'] ?? '',
            'certificateLink' => $data['certificateLink'] ?? ''
        );
        $qrId = insertQRData($qrData);

        if ($qrId) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "QR Code inserted successfully",
            "qrId" => $qrId));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert QR Code"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'updateQr') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $qrId = $_GET['qrId'] ?? '';
        if (!empty($qrId)) {
            $data = json_decode(file_get_contents('php://input'), true);

            $data = array(
                'qrUniqueKey' => $data['qrUniqueKey'] ?? '',
                'clientId' => $data['clientId'] ?? '',
                'departmentName' => $data['departmentName'] ?? '',
                'serialNumber' => $data['serialNumber'] ?? '',
                'contractorName' =>$data['contractorName']?? '',
                'swl' => $data['swl'] ?? '',
                'swp' => $data['swp'] ?? '',
                'certificateIssueDate' => $data['certificateIssueDate'] ?? '',
                'certificateExpDate' => $data['certificateExpDate'] ?? '',
                'certificateLink' => $data['certificateLink'] ?? ''
            );

            $updated = UpdateQRData($qrId, $data);

            if ($updated) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "QR Code updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update QR Code"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "QR ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} 
elseif ($endpoint === 'deleteQr') {
    $authenticated = authenticateUser();
     if ($authenticated) {
        $qrId = $_GET['qrId'] ?? '';
        if (!empty($qrId)) {
            $deleted = deleteQRData($qrId);
            if ($deleted) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "QR deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete QR"));
            }
            
        }
         else {
            http_response_code(400);
            echo json_encode(array("message" => "Inquiry ID is required"));
        }
     }
     else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
//  else {
//     http_response_code(404);
//     echo json_encode(array("message" => "Endpoint not found"));
}
elseif ($endpoint === 'fetchWithQrId') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $qrId = $_GET['qrId'] ?? '';
        
        if (!empty($qrId)) {
            $qrDetails = fetchAllQrByID($qrId);

            if ($qrDetails !== null) {
                header('Content-Type: application/json');
                echo json_encode($qrDetails);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "QR Code not found"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "QR ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchAllQr') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        
        $allQrCodes = fetchAllQr();
        if ($allQrCodes !== null) {
                header('Content-Type: application/json');
                echo json_encode($allQrCodes);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "QR details not found"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} 
elseif ($endpoint === 'fetchAllQrNew') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        
        $allQrCodes = fetchAllQrNew();
        if ($allQrCodes !== null) {
                header('Content-Type: application/json');
                echo json_encode($allQrCodes);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "QR details not found"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} 
// elseif ($endpoint === 'fetchAllQrByID') {
//     $authenticated = authenticateUser();

//     if ($authenticated) {
        
//         $allQrCodes = fetchAllQrByID();
//         if ($allQrCodes !== null) {
//                 header('Content-Type: application/json');
//                 echo json_encode($allQrCodes);
//             } else {
//                 http_response_code(404);
//                 echo json_encode(array("message" => "QR details not found"));
//             }
//     } else {
//         http_response_code(401);
//         echo json_encode(array("message" => "Unauthorized"));
//     }
// }

else {
    http_response_code(404);
    echo json_encode(array("message" => "Endpoint not found"));
}
?>
