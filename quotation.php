<?php
require_once 'functionsQuotation.php';
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

if ($endpoint === 'insertQuotation') {
    
     // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new quotation
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract quotation data from the request
        $quotationData = array(
            'qutReference' => $data['qutReference'] ?? '',
            'date' => $data['date'] ?? '',
            'status' => $data['status'] ?? '',
            'subject' => $data['subject'] ?? '',
            'clientId' => $data['clientId'] ?? '',
            'contactName' => $data['contactName'] ?? '',
            'termAndCondition' => $data['termAndCondition'] ?? '',
            'scopeOfWork' => $data['scopeOfWork'] ?? '',
            'version' => $data['version'] ?? '',
            'createdBy' => $data['created_by'] ?? ''
        );

        // Extract item data from the request
        $itemsData = $data['items'] ?? [];

        // Insert quotation data into the database
        $quotationId = insertQuotation($quotationData);
        
        if ($quotationId) {
            // If quotation insertion is successful, insert item data
            foreach ($itemsData as $item) {
                // Add the quotation ID to each item before insertion
                $itemDetails = $item['itemDetails'];
                $sacCode = $item['sacCode'];
                $uom = $item['uom'];
                $qty = $item['qty'];
                $unitRate = $item['unitRate'];
                insertQuotationItem($quotationId, $itemDetails, $sacCode, $uom, $qty, $unitRate);
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Quotation inserted successfully"));
        } else {
            // Return error response if quotation insertion fails
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert quotation"));
        }
}
    
} elseif ($endpoint === 'updateQuotation') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the quotation ID from the request
        $qutId = $_GET['qutId'] ?? '';
        if (!empty($qutId)) {
            // Extract quotation data from the request
            $data = json_decode(file_get_contents('php://input'), true);

            $quotationData = array(
                'qutReference' => $data['qutReference'] ?? '',
                'date' => $data['date'] ?? '',
                'status' => $data['status'] ?? '',
                'subject' => $data['subject'] ?? '',
                'clientId' => $data['clientId'] ?? '',
                'contactName' => $data['contactName'] ?? '',
                'termAndCondition' => $data['termAndCondition'] ?? '',
                'scopeOfWork' => $data['scopeOfWork'] ?? '',
                'version' => $data['version'] ?? '',
                'createdBy' => $data['created_by'] ?? ''
            );

            // Extract item data from the request
            $itemsData = $data['items'] ?? [];

            // Update quotation data in the database
            $updated = updateQuotation($qutId, $quotationData, $itemsData);

            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Quotation updated successfully"));
            } else {
                // Return error response if quotation update fails
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update quotation"));
            }
        } else {
            // Return error response if quotation ID is missing
            http_response_code(400);
            echo json_encode(array("message" => "Quotation ID is required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'draftQuotation') {
    
     // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new quotation
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract quotation data from the request
        $quotationData = array(
            'qutReference' => $data['qutReference'] ?? '',
            'date' => $data['date'] ?? '',
            'status' => $data['status'] ?? '',
            'subject' => $data['subject'] ?? '',
            'clientId' => $data['clientId'] ?? '',
            'contactName' => $data['contactName'] ?? '',
            'termAndCondition' => $data['termAndCondition'] ?? '',
            'scopeOfWork' => $data['scopeOfWork'] ?? '',
            'version' => $data['version'] ?? '',
            'createdBy' => $data['created_by'] ?? ''
        );

        // Extract item data from the request
        $itemsData = $data['items'] ?? [];

        // Insert quotation data into the database
        $quotationId = insertQuotation($quotationData);
        
        if ($quotationId) {
            // If quotation insertion is successful, insert item data
            foreach ($itemsData as $item) {
                // Add the quotation ID to each item before insertion
                $itemDetails = $item['itemDetails'];
                $sacCode = $item['sacCode'];
                $uom = $item['uom'];
                $qty = $item['qty'];
                $unitRate = $item['unitRate'];
                insertQuotationItem($quotationId, $itemDetails, $sacCode, $uom, $qty, $unitRate);
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Quotation drafted successfully"));
        } else {
            // Return error response if quotation insertion fails
            http_response_code(500);
            echo json_encode(array("message" => "Failed to draft quotation"));
        }
}
    
} elseif ($endpoint === 'fetchQuotation') {
    // Fetch all quotations
    $allQuotations = fetchAllQuotations();
    // Return JSON response with quotations
    header('Content-Type: application/json');
    echo json_encode($allQuotations);
}

elseif ($endpoint === 'fetchQuotationNew') {
    header('Content-Type: application/json');
    
    $authenticated = authenticateUser();
    
    if ($authenticated) {
        $allQuotations = fetchAllQuotationslist();
        echo json_encode($allQuotations);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized"]);
    }
}


elseif ($endpoint === 'fetchDraftQuotation') {
    // Fetch all quotations
    $allQuotations = fetchAllDraftQuotations();
    // Return JSON response with quotations
    header('Content-Type: application/json');
    echo json_encode($allQuotations);
} elseif ($endpoint === 'updateQuotation') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new quotation
        $data = json_decode(file_get_contents('php://input'), true);

        // Extract quotation data from the request
        $quotationData = array(
            'qutReference' => $data['qutReference'] ?? '',
            'date' => $data['date'] ?? '',
            'subject' => $data['subject'] ?? '',
            'clientId' => $data['clientId'] ?? '',
            'contactId' => $data['contactId'] ?? '',
            'scopeOfWork' => $data['scopeOfWork'] ?? '',
            'version' => $data['version'] ?? '',
            'createdBy' => $data['createdBy'] ?? ''
        );

        // Extract item data from the request
        $itemsData = $data['items'] ?? [];

        // Insert quotation data into the database
        $quotationId = insertQuotation($quotationData);

        if ($quotationId) {
            // If quotation insertion is successful, insert item data
            foreach ($itemsData as $item) {
                // Add the quotation ID to each item before insertion
                $item['quotationId'] = $quotationId;
                insertQuotationItem($item);
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Quotation inserted successfully"));
        } else {
            // Return error response if quotation insertion fails
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert quotation"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'deleteQuotation') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, delete the quotation
        $data = json_decode(file_get_contents('php://input'), true);

        $qutId = $data['qutId'] ?? '';

        if ($qutId) {
            $deleted = deleteQuotation($qutId);

            if ($deleted) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Quotation deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete quotation"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Quotation ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'getNextQuotationNumber') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Fetch the next quotation number
        $nextQuotationNumber = getNextQuotationNumber();
    
        // Return the quotation number as JSON response
        header('Content-Type: application/json');
    echo json_encode($nextQuotationNumber);
    } else {
            http_response_code(400);
            echo json_encode(array("message" => "Quotation ID is required"));
        }
} elseif ($endpoint === 'fetchQuotationDetails') {
     $authenticated = authenticateUser();

    if ($authenticated) {
    $qutId = $_GET['qutId'] ?? '';

            if ($qutId !== '') {
                // Fetch quotation details along with items based on qutId
                $quotationDetails = fetchQuotationDetails($qutId);
        
                if ($quotationDetails !== null) {
                    // Return JSON response with quotation details and items
                    header('Content-Type: application/json');
                    echo json_encode($quotationDetails);
                } else {
                    // Return error response if fetching fails
                    http_response_code(404);
                    echo json_encode(array("message" => "Quotation not found"));
                }
            } else {
                // Return error response if qutId parameter is missing
                http_response_code(400);
                echo json_encode(array("message" => "Quotation ID is required"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchQuotationVersionInfo') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the qutReference from the request
        $qutReference = $_GET['qutReference'] ?? '';

        if (!empty($qutReference)) {
            // Fetch version, qutId, and created_at based on qutReference
            $quotationInfo = fetchQuotationVersionInfoByReference($qutReference);

            if ($quotationInfo !== null) {
                // Return JSON response with version, qutId, and created_at
                header('Content-Type: application/json');
                echo json_encode($quotationInfo);
            } else {
                // Return error response if fetching fails
                http_response_code(404);
                echo json_encode(array("message" => "Quotation information not found"));
            }
        } else {
            // Return error response if qutReference parameter is missing
            http_response_code(400);
            echo json_encode(array("message" => "Quotation reference is required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchScopeOfWork') {
    // Fetch all scope of work templates
    $allScopeOfWork = fetchAllScopeOfWork();

    // Return JSON response with scope of work templates
    header('Content-Type: application/json');
    echo json_encode($allScopeOfWork);
} elseif ($endpoint === 'fetchScopeOfWorkById') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the scopeOfWorkId from the request
        $scopeOfWorkId = $_GET['scopeOfWorkId'] ?? '';

        if (!empty($scopeOfWorkId)) {
            // Fetch scope of work template by ID
            // Implement your logic to fetch a single scope of work template by ID
            // Replace 'fetchScopeOfWorkById' with your actual function
            $scopeOfWork = fetchScopeOfWorkById($scopeOfWorkId);

            if ($scopeOfWork !== null) {
                // Return JSON response with scope of work template
                header('Content-Type: application/json');
                echo json_encode($scopeOfWork);
            } else {
                // Return error response if fetching fails
                http_response_code(404);
                echo json_encode(array("message" => "Scope of work template not found"));
            }
        } else {
            // Return error response if scopeOfWorkId parameter is missing
            http_response_code(400);
            echo json_encode(array("message" => "Scope of work ID is required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateQuotationStatus') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the quotation reference and version from the request
        $qutId = $_GET['qutId'] ?? '';
        $status = $_GET['status'] ?? '';
        
        if (!empty($qutId)) {
            // Update the quotation status
            $updated = updateQuotationStatus($qutId, $status);

            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Quotation status updated successfully"));
            } else {
                // Return error response if update fails
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update quotation status"));
            }
        } else {
            // Return error response if required parameters are missing
            http_response_code(400);
            echo json_encode(array("message" => "Quotation reference and version are required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchTermConditions') {
    $allTermConditions = fetchAllTermConditions();

    header('Content-Type: application/json');
    echo json_encode($allTermConditions);
}elseif ($endpoint === 'updateQuotationStatusToClosed') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the quotation reference, version, and closed remarks from the request
        $qutId = $_GET['qutId'] ?? '';
        $reasonForClose = $_GET['reasonForClose'] ?? '';
        
        if (!empty($qutId) && !empty($reasonForClose)) {
            // Update the quotation status to "Closed" and save the closed remarks
            $updated = updateQuotationStatusToClosed($qutId, $reasonForClose);

            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Quotation status updated to Closed successfully"));
            } else {
                // Return error response if update fails
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update quotation status to Closed"));
            }
        } else {
            // Return error response if required parameters are missing
            http_response_code(400);
            echo json_encode(array("message" => "Quotation qutId and reason for close are required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'insertInquiry') {
    $authenticated = authenticateUser();
    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        $inquiryData = array(
            'inquiryDate' => $data['inquiryDate'] ?? '',
            'status' => $data['status'] ?? '',
            'clientId' => $data['clientId'] ?? '',
            'contactId' => $data['contactId'] ?? '',
            'workDescription' => $data['workDescription'] ?? '',
            'remarks' => $data['remarks'] ?? ''
        );
    
        $inquiryId = insertInquiry($inquiryData);
    
        if ($inquiryId) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Inquiry inserted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert inquiry"));
        }
    }else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchAllInquiries') {
    $authenticated = authenticateUser();
    if ($authenticated) {
    $allInquiries = fetchAllInquiries();
    header('Content-Type: application/json');
    echo json_encode($allInquiries);
    }else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}
elseif ($endpoint === 'fetchAllInquiriesNew') {
    header('Content-Type: application/json');
    
    $authenticated = authenticateUser();
    // echo($authenticated);
    if ($authenticated) {
        $allInquiries = fetchAllInquiriesList();
         header('Content-Type: application/json');
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized"]);
    }
}


elseif ($endpoint === 'fetchAllInquiriesForExcel') {
    $authenticated = authenticateUser();
    if ($authenticated) {
    $allInquiries = fetchAllInquiriesForExcel();
    header('Content-Type: application/json');
    echo json_encode($allInquiries);
    }else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'fetchInquiryById') {
     $authenticated = authenticateUser();
    if ($authenticated) {
    $inquiryId = $_GET['inquiryId'] ?? '';
    if (!empty($inquiryId)) {
        $inquiry = fetchInquiryById($inquiryId);
        if ($inquiry) {
            header('Content-Type: application/json');
            echo json_encode($inquiry);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Inquiry not found"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Inquiry ID is required"));
    }
}else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'updateInquiry') {
     $authenticated = authenticateUser();
    if ($authenticated) {
    $data = json_decode(file_get_contents('php://input'), true);
    $inquiryId = $data['inquiryId'] ?? '';
    $inquiryData = array(
        'inquiryDate' => $data['inquiryDate'] ?? '',
        'status' => $data['status'] ?? '',
        'clientId' => $data['clientId'] ?? '',
        'contactId' => $data['contactId'] ?? '',
        'workDescription' => $data['workDescription'] ?? '',
        'remarks' => $data['remarks'] ?? ''
        
    );

    if (!empty($inquiryId)) {
        $updated = updateInquiry($inquiryId, $inquiryData);
        if ($updated) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Inquiry updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update inquiry"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Inquiry ID is required"));
    }
    }else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'updateInquiryStatus') {
     $authenticated = authenticateUser();
    if ($authenticated) {
    $data = json_decode(file_get_contents('php://input'), true);
    $inquiryId = $data['inquiryId'] ?? '';
    $inquiryData = array(
        'status' => $data['status'] ?? '',
        'closeComments' => $data['closeComments'] ?? '',
    );

    if (!empty($inquiryId)) {
        $updated = updateInquiryStatus($inquiryId, $inquiryData);
        if ($updated) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Inquiry updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update inquiry"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Inquiry ID is required"));
    }
    }else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'deleteInquiry') {
    $authenticated = authenticateUser();
    if ($authenticated) {
    $inquiryId = $_GET['inquiryId'] ?? '';
    if (!empty($inquiryId)) {
        $deleted = deleteInquiry($inquiryId);
        if ($deleted) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Inquiry deleted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to delete inquiry"));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Inquiry ID is required"));
    }
}else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}else {
    http_response_code(404);
    echo json_encode(array("message" => "Endpoint not found"));
}


?>
