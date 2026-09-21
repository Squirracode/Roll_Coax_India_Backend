<?php
require_once 'functions.php';
require_once 'functionsInventory.php';

// Allow CORS from any origin unconditionally
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origin}");
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';

if ($endpoint === 'insertInventory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $role = $_POST['role'] ?? ($_GET['role'] ?? '');
        if ($role && !in_array($role, ['Admin', 'Master Admin'])) {
            http_response_code(403);
            echo json_encode(array("message" => "Forbidden: Only Admin can add inventory"));
            exit();
        }

        $isStoreAsset  = $_POST['isStoreAsset']  ?? 0;
        $isConsumable  = $_POST['isConsumable']  ?? 0;
        $name          = $_POST['name']          ?? '';
        $category      = $_POST['category']      ?? '';
        $type          = $_POST['type']          ?? '';
        $status        = $_POST['status']        ?? 'Available';
        $total         = $_POST['total']         ?? 1;
        $available     = $_POST['available']     ?? 1;
        $serialNumber  = $_POST['serialNumber']  ?? '';
        $unitOfMeasure = $_POST['unitOfMeasure'] ?? 'Pcs';
        $image_path    = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/inventory/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid('inv_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            }
        }

        if ($name && $category && $type) {
            $inserted = addInventory($isStoreAsset, $isConsumable, $name, $category, $type, $status, $total, $available, $serialNumber, $unitOfMeasure, $image_path);
            if ($inserted === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Inventory item inserted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to insert inventory item", "error" => $inserted));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getInventory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $inventory = getAllInventory();
        if ($inventory !== false) {
            header('Content-Type: application/json');
            echo json_encode($inventory);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to retrieve inventory"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'addInventoryCategory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $data['name'] ?? '';
        $isStoreAsset = $data['isStoreAsset'] ?? 0;
        $isConsumable = $data['isConsumable'] ?? 0;

        if ($name) {
            $inserted = addInventoryCategory($name, $isStoreAsset, $isConsumable);
            if ($inserted === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Category inserted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to insert category", "error" => $inserted));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Category name is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getInventoryCategories') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $categories = getInventoryCategories();
        if ($categories !== false) {
            header('Content-Type: application/json');
            echo json_encode($categories);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to retrieve categories"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'updateInventory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
        } else {
            $data = $_POST;
            $id = $_POST['id'] ?? 0;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/inventory/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid('inv_', true) . '.' . $file_ext;
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $data['image_path'] = $target_file;
                }
            }
        }

        $role = $data['role'] ?? ($_POST['role'] ?? '');
        if ($role && !in_array($role, ['Admin', 'Master Admin'])) {
            http_response_code(403);
            echo json_encode(array("message" => "Forbidden: Only Admin can update inventory"));
            exit();
        }

        if ($id) {
            $updated = updateFullInventoryRecord($id, $data);
            if ($updated === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Inventory item updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update inventory item", "error" => $updated));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'deleteInventory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        $role = $data['role'] ?? ($_GET['role'] ?? '');
        if ($role && !in_array($role, ['Admin', 'Master Admin'])) {
            http_response_code(403);
            echo json_encode(array("message" => "Forbidden: Only Admin can delete inventory"));
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? ($_GET['id'] ?? 0);

        if ($id) {
            $deleted = deleteInventoryItem($id);
            if ($deleted === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Inventory item deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete inventory item", "error" => $deleted));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'submitInventoryRequest') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $role = $data['role'] ?? '';
        if ($role && !in_array($role, ['Execution', 'Admin', 'BD', 'Account', 'Master Admin'])) {
            http_response_code(403);
            echo json_encode(array("message" => "Forbidden role"));
            exit();
        }

        $inventory_id        = $data['inventory_id']        ?? 0;
        $item_name           = $data['item_name']           ?? '';
        $item_category       = $data['item_category']       ?? '';
        $operator_name       = $data['operator_name']       ?? '';
        $requester_name      = $data['requester_name']      ?? '';
        $requested_by        = $data['requested_by']        ?? '';
        $required_from       = $data['required_from']       ?? '';
        $required_from_time  = $data['required_from_time']  ?? '09:00';
        $required_until      = $data['required_until']      ?? '';
        $required_until_time = $data['required_until_time'] ?? '17:00';
        $purpose             = $data['purpose']             ?? '';
        $quantity_requested  = $data['quantity_requested']  ?? 1;
        $uom                 = $data['uom']                 ?? 'Pcs';

        if ($inventory_id && $item_name && $requester_name && $requested_by && $required_from && $required_from_time && $purpose) {
            $result = submitInventoryRequest($inventory_id, $item_name, $item_category, $operator_name, $requester_name, $requested_by, $required_from, $required_from_time, $required_until, $required_until_time, $purpose, $quantity_requested, $uom);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Inventory request submitted successfully"));
            } else {
                http_response_code(400);
                echo json_encode(array("message" => $result, "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getInventoryRequestsByUser') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $requested_by = $_GET['requested_by'] ?? '';

        if ($requested_by) {
            $requests = getInventoryRequestsByUser($requested_by);
            if ($requests !== false) {
                header('Content-Type: application/json');
                echo json_encode($requests);
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to retrieve inventory requests"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "requested_by parameter is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getAllInventoryRequests') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $requests = getAllInventoryRequests();
        if ($requests !== false) {
            header('Content-Type: application/json');
            echo json_encode($requests);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to retrieve all inventory requests"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'updateInventoryRequestStatus') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $role = $data['role'] ?? ($_GET['role'] ?? '');
        if ($role && !in_array($role, ['Admin', 'Master Admin'])) {
            http_response_code(403);
            echo json_encode(array("message" => "Forbidden: Only Admin can update inventory request status"));
            exit();
        }

        $id                  = $data['id']                  ?? 0;
        $status              = $data['status']              ?? '';
        $admin_note          = $data['admin_note']          ?? '';
        $issued_to           = $data['issued_to']           ?? null;
        $returned_by         = $data['returned_by']         ?? null;
        $condition_on_return = $data['condition_on_return'] ?? null;
        $approved_quantity   = $data['approved_quantity']   ?? null;
        $required_from       = $data['required_from']       ?? null;
        $required_from_time  = $data['required_from_time']  ?? null;
        $required_until      = $data['required_until']      ?? null;
        $required_until_time = $data['required_until_time'] ?? null;

        if ($id && $status) {
            $result = updateInventoryRequestStatus($id, $status, $admin_note, $issued_to, $returned_by, $condition_on_return, $approved_quantity, $required_from, $required_from_time, $required_until, $required_until_time);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Inventory request status updated successfully"));
            } else {
                http_response_code(400);
                echo json_encode(array("message" => $result, "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID and status are required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getDamagedItems') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $items = getDamagedItems();
        header('Content-Type: application/json');
        echo json_encode($items !== false ? $items : []);
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'markItemRepaired') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        $damage_log_id = $data['damage_log_id'] ?? 0;
        $repair_note   = $data['repair_note']   ?? '';

        if ($damage_log_id) {
            $result = markItemRepaired($damage_log_id, $repair_note);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Item marked as repaired"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed", "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "damage_log_id is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'getInventoryHistory') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $inventory_id = $_GET['inventory_id'] ?? ($_POST['inventory_id'] ?? 0);
        if ($inventory_id) {
            $history = getInventoryHistory($inventory_id);
            if ($history !== false) {
                header('Content-Type: application/json');
                echo json_encode($history);
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to retrieve inventory history"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "inventory_id is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'addInventoryItem') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $type       = $data['type']       ?? 'Reusable';
        $item_name  = $data['item_name']  ?? '';
        $item_make  = $data['item_make']  ?? '';
        $image_link = $data['image_link'] ?? '';

        if ($item_name) {
            $result = addInventoryItem($type, $item_name, $item_make, $image_link);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Item added successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to add item", "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "item_name is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'fetchInventoryItems') {
    $items = fetchInventoryItems();
    header('Content-Type: application/json');
    echo json_encode($items);

} elseif ($endpoint === 'updateInventoryItem') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $id         = $data['id']         ?? 0;
        $type       = $data['type']       ?? 'Reusable';
        $item_name  = $data['item_name']  ?? '';
        $item_make  = $data['item_make']  ?? '';
        $image_link = $data['image_link'] ?? '';

        if ($id && $item_name) {
            $result = updateMasterCatalogItem($id, $type, $item_name, $item_make, $image_link);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Item updated successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to update item", "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID and item_name are required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'deleteInventoryItem') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? ($_GET['id'] ?? 0);

        if ($id) {
            $result = deleteMasterCatalogItem($id);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Item deleted successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to delete item", "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID is required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'insertInventoryWithFiles') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = $_POST;

        if (isset($_FILES['purchase_invoice']) && $_FILES['purchase_invoice']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['purchase_invoice']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('inv_invoice_', true) . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['purchase_invoice']['tmp_name'], $target)) {
                $data['purchase_invoice_path'] = $target;
            }
        }

        if (isset($_FILES['calibration_cert']) && $_FILES['calibration_cert']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['calibration_cert']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('inv_cal_', true) . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['calibration_cert']['tmp_name'], $target)) {
                $data['calibration_cert_path'] = $target;
            }
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('inv_img_', true) . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $data['image_path'] = $target;
            }
        }

        $result = insertInventoryWithFiles($data);
        if ($result === true) {
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Inventory added successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to add inventory", "error" => $result));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'cancelInventoryRequest') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $id           = $data['id']           ?? 0;
        $requested_by = $data['requested_by'] ?? '';

        if ($id && $requested_by) {
            $result = cancelInventoryRequest($id, $requested_by);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Request cancelled successfully"));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to cancel request", "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "id and requested_by are required"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }

} elseif ($endpoint === 'updateUserPendingRequest') {
    $authenticated = authenticateUser();

    if ($authenticated) {
        $data = json_decode(file_get_contents('php://input'), true);

        $id                  = $data['id']                  ?? 0;
        $requester_name      = $data['requester_name']      ?? '';
        $operator_name       = $data['operator_name']       ?? '';
        $requested_by        = $data['requested_by']        ?? '';
        $required_from       = $data['required_from']       ?? '';
        $required_from_time  = $data['required_from_time']  ?? '';
        $required_until      = $data['required_until']      ?? '';
        $required_until_time = $data['required_until_time'] ?? '';
        $purpose             = $data['purpose']             ?? '';
        $quantity_requested  = $data['quantity_requested']  ?? 1;
        $uom                 = $data['uom']                 ?? 'Pcs';

        if ($id && $requested_by && $requester_name && $required_from) {
            $result = updateUserPendingRequest($id, $requested_by, $requester_name, $operator_name, $required_from, $required_from_time, $required_until, $required_until_time, $purpose, $quantity_requested, $uom);
            if ($result === true) {
                header('Content-Type: application/json');
                echo json_encode(array("message" => "Request updated successfully"));
            } else {
                http_response_code(400);
                echo json_encode(array("message" => $result, "error" => $result));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields for update"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
}
?>
