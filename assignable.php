<?php
require_once 'functionsAssignable.php';
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

if ($endpoint === 'insertAssignable') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // If authenticated, insert a new assignable
        $data = json_decode(file_get_contents('php://input'), true);
        // Extract assignable data from the request
        $assignableData = array(
            'qutId' => $data['qutId'] ?? null,
            'poDate' => $data['poDate'] ?? null,
            'status' => $data['status'] ?? '',
            'subject' => $data['subject'] ?? '',
            'clientId' => $data['clientId'] ?? null,
            'contactName' => $data['contactName'] ?? '',
            'created_by' => $data['created_by'] ?? '',
            'scopeOfWork' => $data['scopeOfWork'] ?? '',
            'termAndCondition' => $data['termAndCondition'] ?? '',
            'poNumber' => $data['poNumber'] ?? '',
            'poFilePath' => $data['poFilePath'] ?? '',
            'poRequired' => $data['poRequired'] ?? 0,
            'poPending' => $data['poPending'] ?? 0
        );
        
        $itemsData = $data['items'] ?? [];
        
        // Insert assignable data into the database
        $assignableId = insertAssignable($assignableData);
        
        if ($assignableId) {
            // If quotation insertion is successful, insert item data
            foreach ($itemsData as $item) {
                // Add the quotation ID to each item before insertion
                $itemDetails = $item['itemDetails'];
                $sacCode = $item['sacCode'];
                $uom = $item['uom'];
                $qty = $item['qty'];
                $unitRate = $item['unitRate'];
                insertAssignableItem($assignableId, $itemDetails, $sacCode, $uom, $qty, $unitRate);
            }
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Assignable inserted successfully"));
        } else {
            // Return error response if insertion fails
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert assignable"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchAssignables') {
    // Fetch all quotations
    $allAssignables = fetchAllAssignables();
    // Return JSON response with quotations
    header('Content-Type: application/json');
    echo json_encode($allAssignables);
}
elseif ($endpoint === 'fetchAssignablesNew') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Fetch all assignables
        $allAssignables = fetchAllAssignablesNew();
        
        // Return JSON response with assignables
        header('Content-Type: application/json');
        echo json_encode($allAssignables);
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}

elseif ($endpoint === 'fetchAssignableDetails') {
     $authenticated = authenticateUser();

    if ($authenticated) {
    $assignableId = $_GET['assignableId'] ?? '';

            if ($assignableId !== '') {
                $assignableDetails = fetchAssignableDetails($assignableId);
        
                if ($assignableDetails !== null) {
                    
                    header('Content-Type: application/json');
                    echo json_encode($assignableDetails);
                } else {
                    // Return error response if fetching fails
                    http_response_code(404);
                    echo json_encode(array("message" => "Assignable not found"));
                }
            } else {
                // Return error response if assignableId parameter is missing
                http_response_code(400);
                echo json_encode(array("message" => "Assignable ID is required"));
            }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'insertComment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        $commentText = $data['commentText'] ?? '';
        $commentBy = $data['commentBy'] ?? '';
        $commentDate = $data['commentDate'] ?? '';
        $assignableId = $data['assignableId'] ?? '';

        if ($commentText !== '' && $commentBy !== '' && $assignableId !== '') {
            // Insert comment into the database
            $success = insertComment($commentText, $commentBy, $commentDate, $assignableId);
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Comment inserted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to insert comment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchComments') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $assignableId = $_GET['assignableId'] ?? '';

        if ($assignableId !== '') {
            // Fetch comments from the database
            $comments = fetchComments($assignableId);
            header('Content-Type: application/json');
            echo json_encode($comments);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Assignable ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'deleteComment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $commentId = $_GET['commentId'] ?? '';

        if ($commentId !== '') {
            // Delete comment from the database
            $success = deleteComment($commentId);
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Comment deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete comment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Comment ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchTeams') {
    // Fetch all teams
    $allTeams = fetchTeams();
    // Return JSON response with teams
    header('Content-Type: application/json');
    echo json_encode($allTeams);
}elseif ($endpoint === 'insertAssignment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Get the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract assignment data from the request
        $teamId = $data['teamId'] ?? null;
        $assignStatus = $data['assignStatus'] ?? '';
        $active = $data['active'] ?? 1;
        $assignableId = $data['assignableId'] ?? null;
        $assignDate = $data['assignDate'] ?? null;
        // Check if all required data is present
        if ($teamId !== null && $assignStatus !== '' && $assignableId !== null) {
            // Insert assignment into the database
            $assignmentId = insertAssignment($teamId, $assignStatus, $active, $assignableId, $assignDate);
            
            if ($assignmentId) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignment inserted successfully", "assignmentId" => $assignmentId));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to insert assignment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateAssignableStatus') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Retrieve the quotation reference and version from the request
        $assignableId = $_GET['assignableId'] ?? '';
        $status = $_GET['status'] ?? '';
        
        if (!empty($assignableId)) {
            // Update the Assignable status
            $updated = updateAssignableStatus($assignableId, $status);

            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignable status updated successfully"));
            } else {
                // Return error response if update fails
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update Assignable status"));
            }
        } else {
            // Return error response if required parameters are missing
            http_response_code(400);
            echo json_encode(array("message" => "Assignable Id and status are required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateAssignablePodetails') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Retrieve the quotation reference and version from the request
        $assignableId = $data['assignableId'] ?? '';
        $poNumber = $data['poNumber'] ?? '';
        $poDate = $data['poDate'] ?? '';
        $poFilePath = $data['poFilePath'] ?? '';
        
        if (!empty($assignableId) && !empty($poNumber) && !empty($poDate) && !empty($poFilePath)) {
            // Update the Assignable status
            $updated = updateAssignablePodetails($assignableId,$poNumber, $poDate, $poFilePath);

            if ($updated) {
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(array("message" => "PO details updated successfully"));
            } else {
                // Return error response if update fails
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update PO details"));
            }
        } else {
            // Return error response if required parameters are missing
            http_response_code(400);
            echo json_encode(array("message" => "Assignable Id and poNumber and poDate and poFilePath are required"));
        }
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateAssignment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Get the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract assignment data from the request
        $assignableId = $data['assignableId'] ?? null;
        $teamId = $data['teamId'] ?? null;
        $assignStatus = $data['assignStatus'] ?? '';
        $active = $data['active'] ?? 1;
        $assignDate = $data['assignDate'] ?? 1;
        
        // Check if all required data is present
        if ($assignableId !== null && $teamId !== null && $assignStatus !== '') {
            // Update assignment in the database
            $success = updateAssignments($assignableId, $teamId, $assignStatus, $active, $assignDate);
            
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignment updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update assignment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateUserAssignmentStatus') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Get the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract assignment data from the request
        $assignableId = $data['assignableId'] ?? null;
        $teamId = $data['teamId'] ?? null;
        $assignStatus = $data['assignStatus'] ?? '';
        
        // Check if all required data is present
        if ($assignableId != null && $teamId != null && $assignStatus != '') {
            // Update assignment in the database
            $success = updateUserAssignmentStatus($assignableId, $teamId, $assignStatus);
            
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignment updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update assignment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'deleteUserAssignment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Get the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract assignment data from the request
        $userAssignmentId = $data['userAssignmentId'] ?? null;
        // Check if all required data is present
        if ($userAssignmentId != null) {
            // Update assignment in the database
            $success = deleteUserAssignment($userAssignmentId);
            
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignment deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete assignment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchAssignmentsByAssignableId') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $assignableId = $_GET['assignableId'] ?? '';
        $teamId = $_GET['teamId'] ?? '';

        if ($assignableId !== '') {
            // Fetch assignments from the database
            $assignments = fetchAssignmentsByAssignableId($assignableId, $teamId);
            header('Content-Type: application/json');
            echo json_encode($assignments);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Assignable ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchAssignmentsByDetails') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $assignableId = $_GET['assignableId'] ?? '';

        if ($assignableId !== '') {
            // Fetch assignments from the database
            $assignments = fetchAssignmentsByDetails($assignableId);
            header('Content-Type: application/json');
            echo json_encode($assignments);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Assignable ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchAllUserAssignments') {
  $authenticated = authenticateUser();

  if ($authenticated) {
    // Fetch all user assignments from the database
    $allUserAssignments = fetchAllUserAssignments();

    // Return JSON response with user assignments
    header('Content-Type: application/json');
    echo json_encode($allUserAssignments);
  } else {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
  }
}
elseif ($endpoint === 'fetchAllUserAssignmentsNew') {
  $authenticated = authenticateUser();

  if (!$authenticated) {
    // Fetch all user assignments from the database
    $allUserAssignments = fetchAllUserAssignmentsNew();

    // Return JSON response with user assignments
    header('Content-Type: application/json');
    echo json_encode($allUserAssignments);
  } else {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized"));
  }
}
elseif ($endpoint === 'insertInvoice') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract invoice data from the request
        $invoiceData = array(
            'invoiceNumber' => $data['invoiceNumber'] ?? '',
            'invoiceFilePath' => $data['invoiceFilePath'] ?? '',
            'assignableId' => $data['assignableId'] ?? null
        );

        // Insert invoice data into the database
        $inserted = insertInvoice($invoiceData);

        if ($inserted) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Invoice inserted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert invoice"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'deleteInvoice') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $invoiceId = $_GET['invoiceId'] ?? '';

        if ($invoiceId !== '') {
            // Delete invoice from the database
            $deleted = deleteInvoice($invoiceId);

            if ($deleted) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Invoice deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete invoice"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Invoice ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchInvoicesByAssignableId') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $assignableId = $_GET['assignableId'] ?? null;

        if ($assignableId !== null) {
            // Fetch invoices from the database
            $invoices = fetchInvoicesByAssignableId($assignableId);

            if (!empty($invoices)) {
                header('Content-Type: application/json');
                echo json_encode($invoices);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No invoices found for the specified assignable ID"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Assignable ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'deleteAssignableByAssignableId') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Get the input data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Extract assignment data from the request
        $assignableId = $data['assignableId'] ?? null;
        // Check if all required data is present
        if ($assignableId != null) {
            // Update assignment in the database
            $success = deleteAssignableByAssignableId($assignableId);
            
            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Assignable deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete Assignable"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Incomplete data provided"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'insertPayment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Extract payment data from the request
        $paymentData = array(
            'paymentDate' => $data['paymentDate'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'assignableId' => $data['assignableId'] ?? null,
            'invoiceId' => $data['invoiceId'] ?? null,
            'qutReference' => $data['qutReference'] ?? ''
        );

        // Insert payment data into the database
        $inserted = insertPayment($paymentData);

        if ($inserted) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Payment inserted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to insert payment"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'deletePayment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $paymentId = $_GET['paymentId'] ?? '';

        if ($paymentId !== '') {
            // Delete payment from the database
            $deleted = deletePayment($paymentId);

            if ($deleted) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Payment deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete payment"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Payment ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'updatePayment') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Extract payment data from the request
        $paymentId = $data['paymentId'] ?? null;
        $paymentData = array(
            'paymentDate' => $data['paymentDate'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'invoiceId' => $data['invoiceId'] ?? null,
            'assignableId' => $data['assignableId'] ?? null,
            'qutReference' => $data['qutReference'] ?? ''
        );

        // Update payment data in the database
        $updated = updatePayment($paymentId, $paymentData);

        if ($updated) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Payment updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update payment"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} elseif ($endpoint === 'updateInvoicePaymentStatus') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Extract payment data from the request
        $invoiceId = $data['invoiceId'] ?? null;
        $paymentStatus = $data['paymentStatus'] ?? null;
        
        if($paymentStatus != '' && $invoiceId != ''){
            $updated = updateInvoicePaymentStatus($invoiceId, $paymentStatus);
            if ($updated) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Payment updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update payment"));
            }
        }else{
            http_response_code(404);
            echo json_encode(array("message" => "InvoiceId and PaymentStatus Is Required"));
        }
        
        
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchInvoicesWithPaymentsByInvoiceId') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $invoiceId = $_GET['invoiceId'] ?? '';

        if ($invoiceId !== '') {
            // Fetch payments by invoice ID from the database
            $payments = fetchInvoicesWithPaymentsByInvoiceId($invoiceId);
            header('Content-Type: application/json');
            echo json_encode($payments);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Invoice ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'fetchAllInvoicesWithPayments') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Fetch all invoices with payments
        $allInvoicesWithPayments = fetchAllInvoicesWithPayments();
        
        // Return JSON response with invoices and payments
        header('Content-Type: application/json');
        echo json_encode($allInvoicesWithPayments);
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}
elseif ($endpoint === 'fetchAllInvoicesWithPaymentsNew') {
    // Authenticate the user
    $authenticated = authenticateUser();

    if ($authenticated) {
        // Fetch all invoices with payments
        $allInvoicesWithPayments = fetchAllInvoicesWithPaymentsNew();
        
        // Return JSON response with invoices and payments
        header('Content-Type: application/json');
        echo json_encode($allInvoicesWithPayments);
    } else {
        // Return unauthorized response if authentication fails
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}
elseif ($endpoint === 'getRemarks') {
    $authenticated = authenticateUser();
    if ($authenticated) {
        $assignableId = $_GET['assignableId'] ?? '';
        if ($assignableId !== '') {
            $remarks = getRemarks($assignableId);
            header('Content-Type: application/json');
            echo json_encode($remarks);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Assignable ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}elseif ($endpoint === 'updateRemarks') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        // Extract payment data from the request
        $assignableId = $data['assignableId'] ?? null;
        $remarks = $data['remarks'] ?? null;

        // Update payment data in the database
        $updated = updateRemarks($assignableId, $remarks);

        if ($updated) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Remarks updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update Remarks"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}  else {
    http_response_code(404);
    echo json_encode(array("message" => "Endpoint not found"));
}


?>
