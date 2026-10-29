<?php
function addInventory($isStoreAsset, $isConsumable, $name, $category, $type, $status, $total, $available, $serialNumber, $unitOfMeasure, $image_path) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // If it's a consumable item, check if it already exists by name
        if ((int)$isConsumable === 1) {
            $stmt_check = $conn->prepare("SELECT id FROM inventory WHERE isConsumable = 1 AND name = :name LIMIT 1");
            $stmt_check->bindParam(':name', $name);
            $stmt_check->execute();
            $existingItem = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Item exists, update the quantity
                $stmt_update = $conn->prepare("UPDATE inventory SET total = IFNULL(total,0) + :total, available = IFNULL(available,0) + :available WHERE id = :id");
                $stmt_update->bindParam(':total', $total, PDO::PARAM_INT);
                $stmt_update->bindParam(':available', $available, PDO::PARAM_INT);
                $stmt_update->bindParam(':id', $existingItem['id'], PDO::PARAM_INT);
                $stmt_update->execute();
                return true;
            }
        }

        $stmt = $conn->prepare("INSERT INTO inventory (isStoreAsset, isConsumable, name, category, type, status, total, available, serialNumber, unitOfMeasure, image_path) VALUES (:isStoreAsset, :isConsumable, :name, :category, :type, :status, :total, :available, :serialNumber, :unitOfMeasure, :image_path)");
        
        $stmt->bindParam(':isStoreAsset', $isStoreAsset, PDO::PARAM_INT);
        $stmt->bindParam(':isConsumable', $isConsumable, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':total', $total, PDO::PARAM_INT);
        $stmt->bindParam(':available', $available, PDO::PARAM_INT);
        $stmt->bindParam(':serialNumber', $serialNumber);
        $stmt->bindParam(':unitOfMeasure', $unitOfMeasure);
        $stmt->bindParam(':image_path', $image_path);

        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function getAllInventory() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("
            SELECT i.*,
              COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = i.name LIMIT 1)) AS item_image_fallback,
              (SELECT SUM(IFNULL(r.quantity_requested, 1)) FROM inventory_requests r WHERE r.inventory_id = i.id AND r.status = 'Reserved') AS reserved,
              (SELECT SUM(IFNULL(r.quantity_requested, 1)) FROM inventory_requests r WHERE r.inventory_id = i.id AND r.status = 'Issued') AS issued
            FROM inventory i
            ORDER BY i.id DESC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$row) {
            if (empty($row['image_path']) && !empty($row['item_image_fallback'])) {
                $row['image_path'] = $row['item_image_fallback'];
            }
        }
        return $results;
    } catch (PDOException $e) {
        return false;
    }
}

function addInventoryCategory($name, $isStoreAsset, $isConsumable) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO inventory_category (name, isStoreAsset, isConsumable) VALUES (:name, :isStoreAsset, :isConsumable)");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':isStoreAsset', $isStoreAsset, PDO::PARAM_INT);
        $stmt->bindParam(':isConsumable', $isConsumable, PDO::PARAM_INT);

        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function getInventoryCategories() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("SELECT * FROM inventory_category ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function updateInventoryItem($id, $isStoreAsset, $isConsumable, $name, $category, $type, $status, $total, $available, $serialNumber, $unitOfMeasure, $image_path) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($image_path !== null) {
            $sql = "UPDATE inventory SET isStoreAsset=:isStoreAsset, isConsumable=:isConsumable, name=:name, category=:category, type=:type, status=:status, total=:total, available=:available, serialNumber=:serialNumber, unitOfMeasure=:unitOfMeasure, image_path=:image_path WHERE id=:id";
        } else {
            $sql = "UPDATE inventory SET isStoreAsset=:isStoreAsset, isConsumable=:isConsumable, name=:name, category=:category, type=:type, status=:status, total=:total, available=:available, serialNumber=:serialNumber, unitOfMeasure=:unitOfMeasure WHERE id=:id";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':isStoreAsset', $isStoreAsset, PDO::PARAM_INT);
        $stmt->bindParam(':isConsumable', $isConsumable, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':total', $total, PDO::PARAM_INT);
        $stmt->bindParam(':available', $available, PDO::PARAM_INT);
        $stmt->bindParam(':serialNumber', $serialNumber);
        $stmt->bindParam(':unitOfMeasure', $unitOfMeasure);
        if ($image_path !== null) {
            $stmt->bindParam(':image_path', $image_path);
        }

        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function updateFullInventoryRecord($id, $data) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $imagePath = $data['image_path'] ?? ($data['image_link'] ?? null);
        if (!$imagePath && !empty($data['name'])) {
            $stmt_img = $conn->prepare("SELECT image_link FROM inventory_items WHERE item_name = :name LIMIT 1");
            $stmt_img->bindParam(':name', $data['name']);
            $stmt_img->execute();
            $itemRow = $stmt_img->fetch(PDO::FETCH_ASSOC);
            if ($itemRow && !empty($itemRow['image_link'])) {
                $imagePath = $itemRow['image_link'];
            }
        }

        $sql = "UPDATE inventory SET
                isStoreAsset = :isStoreAsset,
                isConsumable = :isConsumable,
                name = :name,
                category = :category,
                item_type = :item_type,
                make = :make,
                model = :model,
                sr_no = :sr_no,
                accessory = :accessory,
                purchase_date = :purchase_date,
                purchase_amount = :purchase_amount,
                purchase_invoice_path = :purchase_invoice_path,
                calibration_date = :calibration_date,
                calibration_due_date = :calibration_due_date,
                calibration_cert_path = :calibration_cert_path,
                operator_name = :operator_name,
                unit_of_measure = :unit_of_measure,
                min_stock_alert = :min_stock_alert,
                total = :total,
                available = :available";
        
        if (!empty($imagePath)) {
            $sql .= ", image_path = :image_path";
        }
        $sql .= " WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $totalVal = isset($data['total']) ? (float)$data['total'] : 1;
        $availVal = isset($data['available']) ? (float)$data['available'] : $totalVal;

        $params = [
            ':id' => (int)$id,
            ':isStoreAsset' => $data['isStoreAsset'] ?? 0,
            ':isConsumable' => $data['isConsumable'] ?? 0,
            ':name' => $data['name'] ?? '',
            ':category' => $data['category'] ?? '',
            ':item_type' => $data['item_type'] ?? ($data['type'] ?? ''),
            ':make' => $data['make'] ?? '',
            ':model' => $data['model'] ?? '',
            ':sr_no' => $data['sr_no'] ?? ($data['serialNumber'] ?? ''),
            ':accessory' => $data['accessory'] ?? '',
            ':purchase_date' => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            ':purchase_amount' => !empty($data['purchase_amount']) ? $data['purchase_amount'] : null,
            ':purchase_invoice_path' => $data['purchase_invoice_path'] ?? '',
            ':calibration_date' => !empty($data['calibration_date']) ? $data['calibration_date'] : null,
            ':calibration_due_date' => !empty($data['calibration_due_date']) ? $data['calibration_due_date'] : null,
            ':calibration_cert_path' => $data['calibration_cert_path'] ?? '',
            ':operator_name' => $data['operator_name'] ?? '',
            ':unit_of_measure' => $data['unit_of_measure'] ?? ($data['uom'] ?? 'Pcs'),
            ':min_stock_alert' => $data['min_stock_alert'] ?? 0,
            ':total' => $totalVal,
            ':available' => $availVal
        ];
        if (!empty($imagePath)) {
            $params[':image_path'] = $imagePath;
        }

        $stmt->execute($params);

        return true;
    } catch (PDOException $e) {
        error_log('updateFullInventoryRecord error: ' . $e->getMessage());
        return $e->getMessage();
    }
}

function deleteInventoryItem($id) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// ─── INVENTORY ITEMS (New Item Flow) ───────────────────────────────────────────

function addInventoryItem($type, $item_name, $item_make, $image_link) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            INSERT INTO inventory_items (type, item_name, item_make, image_link)
            VALUES (:type, :item_name, :item_make, :image_link)
        ");
        $stmt->bindParam(':type',       $type);
        $stmt->bindParam(':item_name',  $item_name);
        $stmt->bindParam(':item_make',  $item_make);
        $stmt->bindParam(':image_link', $image_link);
        
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function fetchInventoryItems() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->query("SELECT * FROM inventory_items ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function updateMasterCatalogItem($id, $type, $item_name, $item_make, $image_link) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Fetch old item details
        $stmt_old = $conn->prepare("SELECT item_name FROM inventory_items WHERE id = :id LIMIT 1");
        $stmt_old->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_old->execute();
        $oldRow = $stmt_old->fetch(PDO::FETCH_ASSOC);
        $oldName = $oldRow ? $oldRow['item_name'] : '';

        // 2. Update item in master catalog
        $stmt = $conn->prepare("
            UPDATE inventory_items 
            SET type = :type, item_name = :item_name, item_make = :item_make, image_link = :image_link
            WHERE id = :id
        ");
        $stmt->bindParam(':type',       $type);
        $stmt->bindParam(':item_name',  $item_name);
        $stmt->bindParam(':item_make',  $item_make);
        $stmt->bindParam(':image_link', $image_link);
        $stmt->bindParam(':id',         $id, PDO::PARAM_INT);
        $stmt->execute();

        // 3. Update matching records in inventory table (name, make, image)
        if (!empty($oldName)) {
            $isConsumable = ($type === 'Consumable') ? 1 : 0;
            $stmt_inv = $conn->prepare("
                UPDATE inventory 
                SET name = :item_name, make = :item_make, isConsumable = :isConsumable, image_path = :image_link
                WHERE name = :old_name OR name = :item_name
            ");
            $stmt_inv->bindParam(':item_name',    $item_name);
            $stmt_inv->bindParam(':item_make',    $item_make);
            $stmt_inv->bindParam(':isConsumable', $isConsumable, PDO::PARAM_INT);
            $stmt_inv->bindParam(':image_link',   $image_link);
            $stmt_inv->bindParam(':old_name',     $oldName);
            $stmt_inv->execute();

            // 4. Update matching records in inventory_requests table
            $stmt_req = $conn->prepare("
                UPDATE inventory_requests
                SET item_name = :item_name
                WHERE item_name = :old_name
            ");
            $stmt_req->bindParam(':item_name', $item_name);
            $stmt_req->bindParam(':old_name',  $oldName);
            $stmt_req->execute();
        }

        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function deleteMasterCatalogItem($id) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function insertInventoryWithFiles($data) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ensure columns exist
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN unit_of_measure VARCHAR(50) DEFAULT 'Pcs'"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN min_stock_alert INT DEFAULT 0"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN image_path TEXT"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory_requests ADD COLUMN quantity_requested DECIMAL(10,2) DEFAULT 1.00"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory_requests ADD COLUMN uom VARCHAR(50) DEFAULT 'Pcs'"); } catch(Exception $e){}

        $isConsumable = $data['isConsumable'] ?? 0;
        $name = $data['name'] ?? '';
        $totalVal = isset($data['total']) ? (float)$data['total'] : 1;
        $availVal = isset($data['available']) ? (float)$data['available'] : $totalVal;

        // Fetch image_path / image_link from inventory_items table if not directly supplied
        $imagePath = $data['image_path'] ?? ($data['image_link'] ?? null);
        if (!$imagePath && !empty($name)) {
            $stmt_img = $conn->prepare("SELECT image_link FROM inventory_items WHERE item_name = :name LIMIT 1");
            $stmt_img->bindParam(':name', $name);
            $stmt_img->execute();
            $itemRow = $stmt_img->fetch(PDO::FETCH_ASSOC);
            if ($itemRow && !empty($itemRow['image_link'])) {
                $imagePath = $itemRow['image_link'];
            }
        }

        // If it's a consumable item, check if it already exists by name
        if ((int)$isConsumable === 1) {
            $stmt_check = $conn->prepare("SELECT id, image_path FROM inventory WHERE isConsumable = 1 AND name = :name LIMIT 1");
            $stmt_check->bindParam(':name', $name);
            $stmt_check->execute();
            $existingItem = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Item exists, update the quantity and image_path if missing
                if (!empty($imagePath) && empty($existingItem['image_path'])) {
                    $stmt_update = $conn->prepare("UPDATE inventory SET total = IFNULL(total,0) + :total, available = IFNULL(available,0) + :available, image_path = :image_path WHERE id = :id");
                    $stmt_update->bindParam(':image_path', $imagePath);
                } else {
                    $stmt_update = $conn->prepare("UPDATE inventory SET total = IFNULL(total,0) + :total, available = IFNULL(available,0) + :available WHERE id = :id");
                }
                $stmt_update->bindParam(':total', $totalVal);
                $stmt_update->bindParam(':available', $availVal);
                $stmt_update->bindParam(':id', $existingItem['id'], PDO::PARAM_INT);
                $stmt_update->execute();
                return true;
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO inventory (
                isStoreAsset, isConsumable, name, category, item_type,
                make, model, sr_no, accessory, purchase_date, purchase_amount,
                purchase_invoice_path, calibration_date, calibration_due_date,
                calibration_cert_path, operator_name, unit_of_measure, min_stock_alert, total, available, image_path
            ) VALUES (
                :isStoreAsset, :isConsumable, :name, :category, :item_type,
                :make, :model, :sr_no, :accessory, :purchase_date, :purchase_amount,
                :purchase_invoice_path, :calibration_date, :calibration_due_date,
                :calibration_cert_path, :operator_name, :unit_of_measure, :min_stock_alert, :total, :available, :image_path
            )
        ");

        $totalVal = isset($data['total']) ? (float)$data['total'] : 1;
        $availVal = isset($data['available']) ? (float)$data['available'] : $totalVal;

        $stmt->execute([
            ':isStoreAsset' => $data['isStoreAsset'] ?? 0,
            ':isConsumable' => $data['isConsumable'] ?? 0,
            ':name' => $data['name'] ?? '',
            ':category' => $data['category'] ?? '',
            ':item_type' => $data['item_type'] ?? '',
            ':make' => $data['make'] ?? '',
            ':model' => $data['model'] ?? '',
            ':sr_no' => $data['sr_no'] ?? '',
            ':accessory' => $data['accessory'] ?? '',
            ':purchase_date' => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            ':purchase_amount' => !empty($data['purchase_amount']) ? $data['purchase_amount'] : null,
            ':purchase_invoice_path' => $data['purchase_invoice_path'] ?? '',
            ':calibration_date' => !empty($data['calibration_date']) ? $data['calibration_date'] : null,
            ':calibration_due_date' => !empty($data['calibration_due_date']) ? $data['calibration_due_date'] : null,
            ':calibration_cert_path' => $data['calibration_cert_path'] ?? '',
            ':operator_name' => $data['operator_name'] ?? '',
            ':unit_of_measure' => $data['unit_of_measure'] ?? ($data['uom'] ?? 'Pcs'),
            ':min_stock_alert' => $data['min_stock_alert'] ?? 0,
            ':total' => $totalVal,
            ':available' => $availVal,
            ':image_path' => $imagePath
        ]);
        
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// ─── INVENTORY REQUEST FUNCTIONS (Execution Role) ────────────────────────────

function submitInventoryRequest($inventory_id, $item_name, $item_category, $operator_name, $requester_name, $requested_by, $required_from, $required_from_time, $required_until, $required_until_time, $purpose, $quantity_requested = 1, $uom = 'Pcs') {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ensure columns exist
        try { $conn->exec("ALTER TABLE inventory_requests ADD COLUMN quantity_requested DECIMAL(10,2) DEFAULT 1.00"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory_requests ADD COLUMN uom VARCHAR(50) DEFAULT 'Pcs'"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory_requests ADD COLUMN ticket_no VARCHAR(50) DEFAULT NULL"); } catch(Exception $e){}

        // Backfill ticket_no for any past requests missing one
        try {
            $conn->exec("UPDATE inventory_requests SET ticket_no = CONCAT('REQ-', YEAR(COALESCE(created_at, NOW())), '-', (1000 + id)) WHERE ticket_no IS NULL OR ticket_no = ''");
        } catch(Exception $e){}

        // Auto-generate unified ticket number: REQ-{YEAR}-{1001, 1002...} across ALL items (Reusable & Consumable)
        $currentYear = date('Y');
        $prefix = "REQ-" . $currentYear . "-";

        // Extract maximum numerical ticket sequence suffix for current year across all inventory_requests rows
        $ticketStmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(ticket_no, '-', -1) AS UNSIGNED)) 
            FROM inventory_requests 
            WHERE ticket_no LIKE :prefix OR ticket_no LIKE :inr_prefix
        ");
        $ticketStmt->execute([
            ':prefix'     => $prefix . '%',
            ':inr_prefix' => 'INR-' . $currentYear . '-%'
        ]);
        $maxNum = (int)$ticketStmt->fetchColumn();

        $nextSeq = ($maxNum >= 1001) ? ($maxNum + 1) : 1001;
        $ticketNo = $prefix . $nextSeq;

        // Check if item is consumable
        $invStmt = $conn->prepare("SELECT isConsumable, available, unit_of_measure FROM inventory WHERE id = ?");
        $invStmt->execute([(int)$inventory_id]);
        $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

        $isConsumable = $inv ? (int)$inv['isConsumable'] : 0;
        $qtyReq = (float)($quantity_requested > 0 ? $quantity_requested : 1);

        if ($isConsumable === 1) {
            $currentAvail = (float)($inv['available'] ?? 0);
            if ($qtyReq > $currentAvail) {
                return "Requested quantity (" . $qtyReq . " " . $uom . ") exceeds current available stock (" . $currentAvail . " " . ($inv['unit_of_measure'] ?? 'units') . ").";
            }
            if (empty($required_until)) {
                $required_until = $required_from;
                $required_until_time = $required_from_time;
            }
        } else {
            // Overlap check for Reusable items
            $req_start = trim($required_from . ' ' . $required_from_time);
            $req_end   = trim($required_until . ' ' . $required_until_time);

            $checkStmt = $conn->prepare("
                SELECT required_from, required_from_time, required_until, required_until_time, status
                FROM inventory_requests
                WHERE inventory_id = :inventory_id
                  AND status IN ('Reserved', 'Issued')
                  AND TIMESTAMP(CONCAT(required_from, ' ', required_from_time)) < TIMESTAMP(:req_end)
                  AND TIMESTAMP(CONCAT(required_until, ' ', required_until_time)) > TIMESTAMP(:req_start)
                ORDER BY required_from ASC
                LIMIT 1
            ");
            $checkStmt->execute([
                ':inventory_id' => (int)$inventory_id,
                ':req_start'    => $req_start,
                ':req_end'      => $req_end
            ]);
            $conflict = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($conflict) {
                $fromStr = date('d M Y h:i A', strtotime($conflict['required_from'] . ' ' . $conflict['required_from_time']));
                $untilStr = date('d M Y h:i A', strtotime($conflict['required_until'] . ' ' . $conflict['required_until_time']));
                return "Item is unavailable for the selected time. It is already " . strtolower($conflict['status']) . " from " . $fromStr . " till " . $untilStr . ".";
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO inventory_requests (ticket_no, inventory_id, item_name, item_category, operator_name, requester_name, requested_by, required_from, required_from_time, required_until, required_until_time, purpose, status, quantity_requested, uom)
            VALUES (:ticket_no, :inventory_id, :item_name, :item_category, :operator_name, :requester_name, :requested_by, :required_from, :required_from_time, :required_until, :required_until_time, :purpose, 'Pending', :quantity_requested, :uom)
        ");
        $stmt->bindParam(':ticket_no',           $ticketNo);
        $stmt->bindParam(':inventory_id',        $inventory_id, PDO::PARAM_INT);
        $stmt->bindParam(':item_name',           $item_name);
        $stmt->bindParam(':item_category',       $item_category);
        $stmt->bindParam(':operator_name',       $operator_name);
        $stmt->bindParam(':requester_name',      $requester_name);
        $stmt->bindParam(':requested_by',        $requested_by);
        $stmt->bindParam(':required_from',       $required_from);
        $stmt->bindParam(':required_from_time',  $required_from_time);
        $stmt->bindParam(':required_until',      $required_until);
        $stmt->bindParam(':required_until_time', $required_until_time);
        $stmt->bindParam(':purpose',             $purpose);
        $stmt->bindParam(':quantity_requested',  $qtyReq);
        $stmt->bindParam(':uom',                 $uom);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function getInventoryRequestsByUser($requested_by) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // $stmt = $conn->prepare("
        //     SELECT r.*,
        //       COALESCE(i.isConsumable, (SELECT (it.type = 'Consumable') FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 0) AS isConsumable,
        //       COALESCE(i.item_type, (SELECT it.type FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 'Reusable') AS item_type,
        //       COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1)) AS image_path,
        //       i.available, i.isStoreAsset, i.unit_of_measure
        //     FROM inventory_requests r
        //     LEFT JOIN inventory i ON r.inventory_id = i.id
        //     WHERE r.requested_by = :requested_by
        //     ORDER BY r.created_at DESC
        // ");
        $stmt = $conn->prepare("
            SELECT r.*,
              COALESCE(i.isConsumable, (SELECT (it.type = 'Consumable') FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 0) AS isConsumable,
              COALESCE(i.item_type, (SELECT it.type FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 'Reusable') AS item_type,
              COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1)) AS image_path,
              i.sr_no, i.make, i.model, i.available, i.isStoreAsset, i.unit_of_measure
            FROM inventory_requests r
            LEFT JOIN inventory i ON r.inventory_id = i.id
            WHERE r.requested_by = :requested_by
            ORDER BY r.created_at DESC
        ");
        $stmt->bindParam(':requested_by', $requested_by);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function cancelInventoryRequest($id, $requested_by) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Only allow cancellation of own Pending requests
        $stmt = $conn->prepare("
            DELETE FROM inventory_requests
            WHERE id = :id AND requested_by = :requested_by AND status = 'Pending'
        ");
        $stmt->bindParam(':id',           $id, PDO::PARAM_INT);
        $stmt->bindParam(':requested_by', $requested_by);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return 'Request not found, already processed, or not owned by this user';
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function updateUserPendingRequest($id, $requested_by, $requester_name, $operator_name, $required_from, $required_from_time, $required_until, $required_until_time, $purpose, $quantity_requested, $uom) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = (int)$id;
        $qtyReq = (float)($quantity_requested > 0 ? $quantity_requested : 1);

        $stmt = $conn->prepare("
            UPDATE inventory_requests
            SET requester_name = :requester_name,
                operator_name = :operator_name,
                required_from = :required_from,
                required_from_time = :required_from_time,
                required_until = :required_until,
                required_until_time = :required_until_time,
                purpose = :purpose,
                quantity_requested = :quantity_requested,
                uom = :uom
            WHERE id = :id AND requested_by = :requested_by AND status = 'Pending'
        ");
        $stmt->bindParam(':id',                  $id, PDO::PARAM_INT);
        $stmt->bindParam(':requested_by',        $requested_by);
        $stmt->bindParam(':requester_name',      $requester_name);
        $stmt->bindParam(':operator_name',       $operator_name);
        $stmt->bindParam(':required_from',       $required_from);
        $stmt->bindParam(':required_from_time',  $required_from_time);
        $stmt->bindParam(':required_until',      $required_until);
        $stmt->bindParam(':required_until_time', $required_until_time);
        $stmt->bindParam(':purpose',             $purpose);
        $stmt->bindParam(':quantity_requested',  $qtyReq);
        $stmt->bindParam(':uom',                 $uom);
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// ─── INVENTORY REQUEST FUNCTIONS (Admin Role) ──────────────────────────────

// function getAllInventoryRequests() {
//     global $host, $dbName, $dbUname, $dbPass;
//     try {
//         $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
//         $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//         $stmt = $conn->prepare("
//             SELECT r.*,
//               COALESCE(i.isConsumable, (SELECT (it.type = 'Consumable') FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 0) AS isConsumable,
//               COALESCE(i.item_type, (SELECT it.type FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 'Reusable') AS item_type,
//               COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1)) AS image_path,
//               i.available, i.isStoreAsset, i.unit_of_measure
//             FROM inventory_requests r
//             LEFT JOIN inventory i ON r.inventory_id = i.id
//             ORDER BY r.created_at DESC
//         ");
//         $stmt->execute();
//         return $stmt->fetchAll(PDO::FETCH_ASSOC);
//     } catch (PDOException $e) {
//         error_log('getAllInventoryRequests error: ' . $e->getMessage());
//         return false;
//     }
// }

function getAllInventoryRequests() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("
            SELECT r.*,
              COALESCE(i.isConsumable, (SELECT (it.type = 'Consumable') FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 0) AS isConsumable,
              COALESCE(i.item_type, (SELECT it.type FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1), 'Reusable') AS item_type,
              COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = r.item_name LIMIT 1)) AS image_path,
              COALESCE(i.sr_no, (SELECT it.sr_no FROM inventory it WHERE it.name = r.item_name AND it.sr_no IS NOT NULL AND it.sr_no != '' LIMIT 1)) AS sr_no,
              i.make, i.model, i.available, i.isStoreAsset, i.unit_of_measure
            FROM inventory_requests r
            LEFT JOIN inventory i ON r.inventory_id = i.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getAllInventoryRequests error: ' . $e->getMessage());
        return false;
    }
}

function updateInventoryRequestStatus($id, $status, $admin_note, $issued_to = null, $returned_by = null, $condition_on_return = null, $approved_quantity = null, $required_from = null, $required_from_time = null, $required_until = null, $required_until_time = null) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = (int)$id;

        // Get current state & item info
        $stmt = $conn->prepare("
            SELECT r.inventory_id, r.status, r.quantity_requested, r.uom, i.isConsumable
            FROM inventory_requests r
            LEFT JOIN inventory i ON r.inventory_id = i.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return "Request not found (id=$id).";
        }

        $inventory_id = (int)$request['inventory_id'];
        $old_status   = $request['status'];
        $isConsumable = (int)($request['isConsumable'] ?? 0);
        $qtyReq       = (float)($request['quantity_requested'] ?? 1);

        // If admin updated the date / time slot for this request
        if (!empty($required_from)) {
            $stmtTime = $conn->prepare("UPDATE inventory_requests SET required_from = ?, required_from_time = ?, required_until = ?, required_until_time = ? WHERE id = ?");
            $stmtTime->execute([$required_from, $required_from_time ?: '09:00', $required_until ?: $required_from, $required_until_time ?: '17:00', $id]);
        }

        // If admin specified a partial/custom approved quantity for consumable items
        if ($isConsumable === 1 && $approved_quantity !== null && (float)$approved_quantity > 0) {
            $qtyReq = (float)$approved_quantity;
            $conn->prepare("UPDATE inventory_requests SET quantity_requested = ? WHERE id = ?")->execute([$qtyReq, $id]);
        }

        // Fetch current item available stock
        $stmtStock = $conn->prepare("SELECT name, available FROM inventory WHERE id = ?");
        $stmtStock->execute([$inventory_id]);
        $invItem = $stmtStock->fetch(PDO::FETCH_ASSOC);
        $availableStock = (float)($invItem['available'] ?? 0);

        // Check if admin is attempting to Accept/Reserve or Issue when stock is insufficient
        if (($status === 'Reserved' || $status === 'Issued') && $old_status === 'Pending') {
            if ($isConsumable === 1) {
                if ($availableStock < $qtyReq) {
                    return "Insufficient stock available! Only {$availableStock} unit(s) available, but {$qtyReq} unit(s) requested.";
                }
            } else {
                if ($availableStock < 1) {
                    return "Insufficient stock available! This item is currently unavailable (0 available stock).";
                }
            }
        }

        // Build update SQL dynamically based on status
        if ($status === 'Issued') {
            $stmt = $conn->prepare("UPDATE inventory_requests SET status=?, admin_note=?, issued_to=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$status, $admin_note, $issued_to, $id]);
        } elseif ($status === 'Returned') {
            $stmt = $conn->prepare("UPDATE inventory_requests SET status=?, admin_note=?, returned_by=?, condition_on_return=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$status, $admin_note, $returned_by, $condition_on_return, $id]);
        } else {
            $stmt = $conn->prepare("UPDATE inventory_requests SET status=?, admin_note=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$status, $admin_note, $id]);
        }

        if ($stmt->rowCount() === 0) {
            return "No rows updated.";
        }

        // Inventory quantity adjustments
        if ($isConsumable === 1) {
            // CONSUMABLE DEDUCTION LOGIC
            if (($status === 'Reserved' || $status === 'Issued') && $old_status === 'Pending') {
                // Permanently deduct from available stock
                $conn->prepare("UPDATE inventory SET available = GREATEST(IFNULL(available,0) - ?, 0), issued = IFNULL(issued,0) + ? WHERE id = ?")->execute([$qtyReq, $qtyReq, $inventory_id]);
            } elseif ($status === 'Issued' && $old_status === 'Reserved') {
                // Already deducted during Reserved stage, no further available deduction needed
            } elseif ($status === 'Rejected' && ($old_status === 'Reserved' || $old_status === 'Issued')) {
                // Restore stock if admin rejects after accepting
                $conn->prepare("UPDATE inventory SET available = IFNULL(available,0) + ?, issued = GREATEST(IFNULL(issued,0) - ?, 0) WHERE id = ?")->execute([$qtyReq, $qtyReq, $inventory_id]);
            }
        } else {
            // REUSABLE ITEM LOGIC
            if ($status === 'Reserved' && $old_status !== 'Reserved') {
                $conn->prepare("UPDATE inventory SET available=GREATEST(IFNULL(available,0)-1,0) WHERE id=? AND available>0")->execute([$inventory_id]);
            } elseif ($status === 'Issued') {
                if ($old_status === 'Pending') {
                    $conn->prepare("UPDATE inventory SET available=GREATEST(IFNULL(available,0)-1,0), issued=IFNULL(issued,0)+1 WHERE id=?")->execute([$inventory_id]);
                } elseif ($old_status === 'Reserved') {
                    $conn->prepare("UPDATE inventory SET issued=IFNULL(issued,0)+1 WHERE id=?")->execute([$inventory_id]);
                }
            } elseif ($status === 'Returned') {
                $condLower = strtolower(trim($condition_on_return ?? ''));
                if (in_array($condLower, ['not ok', 'damaged', 'broken', 'needs repair'])) {
                    // Auto-ensure damage_log table & damaged column exist
                    try { $conn->exec("ALTER TABLE inventory ADD COLUMN damaged INT DEFAULT 0"); } catch (Exception $e){}
                    try {
                        $conn->exec("CREATE TABLE IF NOT EXISTS damage_log (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            inventory_id INT NOT NULL,
                            request_id INT DEFAULT NULL,
                            item_name VARCHAR(255),
                            returned_by VARCHAR(255),
                            damage_note TEXT,
                            status ENUM('Damaged','Repaired') DEFAULT 'Damaged',
                            repair_note TEXT DEFAULT NULL,
                            repaired_at DATETIME DEFAULT NULL,
                            created_at DATETIME DEFAULT NOW(),
                            updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
                        )");
                    } catch (Exception $e){}

                    $conn->prepare("UPDATE inventory SET issued=GREATEST(IFNULL(issued,0)-1,0), damaged=IFNULL(damaged,0)+1 WHERE id=?")->execute([$inventory_id]);
                    $itemNameForLog = !empty($invItem['name']) ? $invItem['name'] : (!empty($request['item_name']) ? $request['item_name'] : 'Damaged Item');
                    $stmt2 = $conn->prepare("
                        INSERT INTO damage_log (inventory_id, request_id, item_name, returned_by, damage_note, status)
                        VALUES (?, ?, ?, ?, ?, 'Damaged')
                    ");
                    $stmt2->execute([$inventory_id, $id, $itemNameForLog, $returned_by, $admin_note]);
                } else {
                    $conn->prepare("UPDATE inventory SET available=available+1, issued=GREATEST(IFNULL(issued,0)-1,0) WHERE id=?")->execute([$inventory_id]);
                }
            } elseif (($status === 'Rejected' || $status === 'Returned') && $old_status === 'Reserved') {
                $conn->prepare("UPDATE inventory SET available=available+1 WHERE id=?")->execute([$inventory_id]);
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log('updateInventoryRequestStatus error: ' . $e->getMessage());
        return $e->getMessage();
    }
}

// ─── Damage Management ────────────────────────────────────────────────────────

function markItemRepaired($damage_log_id, $repair_note = '') {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = (int)$damage_log_id;

        $stmt = $conn->prepare("SELECT inventory_id FROM damage_log WHERE id = ?");
        $stmt->execute([$id]);
        $damageLog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$damageLog) {
            return "Damage record not found.";
        }

        $inventory_id = (int)$damageLog['inventory_id'];

        $stmtUpd = $conn->prepare("
            UPDATE damage_log 
            SET status = 'Repaired', repair_note = ?, repaired_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpd->execute([$repair_note, $id]);

        if ($inventory_id > 0) {
            $conn->prepare("UPDATE inventory SET damaged = GREATEST(IFNULL(damaged,0)-1, 0), available = IFNULL(available,0) + 1 WHERE id = ?")->execute([$inventory_id]);
        }

        return true;
    } catch (PDOException $e) {
        error_log('markItemRepaired error: ' . $e->getMessage());
        return $e->getMessage();
    }
}

function getDamagedItems() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS damage_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                inventory_id INT NOT NULL,
                request_id INT DEFAULT NULL,
                item_name VARCHAR(255),
                returned_by VARCHAR(255),
                damage_note TEXT,
                status ENUM('Damaged','Repaired') DEFAULT 'Damaged',
                repair_note TEXT DEFAULT NULL,
                repaired_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT NOW(),
                updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
            )");
        } catch (Exception $e){}

        // Ensure optional columns exist in inventory to avoid SQL column missing errors
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN unit_of_measure VARCHAR(50) DEFAULT 'Pcs'"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN isConsumable INT DEFAULT 0"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN sr_no VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN serialNumber VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN make VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN model VARCHAR(100) DEFAULT NULL"); } catch(Exception $e){}
        try { $conn->exec("ALTER TABLE inventory ADD COLUMN image_path TEXT DEFAULT NULL"); } catch(Exception $e){}

        // Auto-backfill any returned damaged requests that missed insertion into damage_log
        try {
            $conn->exec("
                INSERT INTO damage_log (inventory_id, request_id, item_name, returned_by, damage_note, status, created_at)
                SELECT r.inventory_id, r.id, r.item_name, r.returned_by, r.admin_note, 'Damaged', COALESCE(r.updated_at, r.created_at, NOW())
                FROM inventory_requests r
                WHERE r.status = 'Returned'
                  AND LOWER(TRIM(r.condition_on_return)) IN ('not ok', 'damaged', 'broken', 'needs repair')
                  AND r.id NOT IN (SELECT request_id FROM damage_log WHERE request_id IS NOT NULL)
            ");
        } catch (Exception $e){}

        $stmt = $conn->prepare("
            SELECT d.id,
                   d.inventory_id,
                   d.request_id,
                   d.returned_by,
                   d.damage_note,
                   d.status,
                   d.repair_note,
                   d.repaired_at,
                   d.created_at,
                   d.updated_at,
                   COALESCE(NULLIF(d.item_name, ''), i.name, 'Damaged Item') AS item_name,
                   COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = d.item_name OR it.item_name = i.name LIMIT 1)) AS image_path,
                   i.make, 
                   i.model, 
                   i.sr_no, 
                   i.serialNumber, 
                   i.category, 
                   i.type, 
                   i.isConsumable, 
                   i.unit_of_measure
            FROM damage_log d
            LEFT JOIN inventory i ON d.inventory_id = i.id
            ORDER BY d.id DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $results !== false ? $results : [];
    } catch (PDOException $e) {
        error_log('getDamagedItems error: ' . $e->getMessage());
        // Fallback simple query if JOIN fails due to missing inventory table/columns
        try {
            if (isset($conn)) {
                $stmtFallback = $conn->prepare("SELECT * FROM damage_log ORDER BY id DESC");
                $stmtFallback->execute();
                return $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $ex) {}
        return [];
    }
}

function resolveDamagedItem($id, $action, $repair_cost, $repair_note, $resolution_by) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = (int)$id;

        // Fetch damage log entry
        $stmt = $conn->prepare("SELECT inventory_id, status FROM damage_log WHERE id = ?");
        $stmt->execute([$id]);
        $damageLog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$damageLog) {
            return "Damage record not found.";
        }

        $inventory_id = (int)$damageLog['inventory_id'];
        $newStatus = ($action === 'repaired') ? 'Repaired' : 'Scrapped';

        // Update damage_log status & details
        $stmtUpd = $conn->prepare("
            UPDATE damage_log 
            SET status = ?, action_taken = ?, repair_cost = ?, repair_note = ?, resolution_by = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpd->execute([$newStatus, $action, $repair_cost ? (float)$repair_cost : 0, $repair_note, $resolution_by, $id]);

        // Inventory stock adjustment
        if ($action === 'repaired') {
            // Repaired: decrement damaged count, increment available count
            $conn->prepare("UPDATE inventory SET damaged = GREATEST(IFNULL(damaged,0)-1, 0), available = IFNULL(available,0) + 1 WHERE id = ?")->execute([$inventory_id]);
        } elseif ($action === 'scrapped') {
            // Scrapped: decrement damaged count and decrement total stock
            $conn->prepare("UPDATE inventory SET damaged = GREATEST(IFNULL(damaged,0)-1, 0), total = GREATEST(IFNULL(total,0)-1, 0) WHERE id = ?")->execute([$inventory_id]);
        }

        return true;
    } catch (PDOException $e) {
        error_log('resolveDamagedItem error: ' . $e->getMessage());
        return $e->getMessage();
    }
}

function getInventoryHistory($inventory_id) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch target item details
        $stmtItem = $conn->prepare("
            SELECT i.*, 
                   COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = i.name LIMIT 1)) AS item_image_fallback
            FROM inventory i 
            WHERE i.id = ? 
            LIMIT 1
        ");
        $stmtItem->execute([(int)$inventory_id]);
        $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

        // Fallback check: If not found by primary key in inventory table, check inventory_items master catalog
        if (!$item) {
            $stmtMaster = $conn->prepare("SELECT * FROM inventory_items WHERE id = ? LIMIT 1");
            $stmtMaster->execute([(int)$inventory_id]);
            $masterItem = $stmtMaster->fetch(PDO::FETCH_ASSOC);

            if ($masterItem) {
                // Find matching entry in inventory table by name
                $stmtItemByName = $conn->prepare("
                    SELECT i.*, 
                           COALESCE(NULLIF(i.image_path, ''), (SELECT it.image_link FROM inventory_items it WHERE it.item_name = i.name LIMIT 1)) AS item_image_fallback
                    FROM inventory i 
                    WHERE i.name = ? 
                    LIMIT 1
                ");
                $stmtItemByName->execute([$masterItem['item_name']]);
                $item = $stmtItemByName->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    $item = [
                        'id' => $inventory_id,
                        'name' => $masterItem['item_name'],
                        'make' => $masterItem['item_make'] ?? '',
                        'category' => 'Master Item',
                        'type' => $masterItem['type'] ?? 'Reusable',
                        'isConsumable' => ($masterItem['type'] === 'Consumable') ? 1 : 0,
                        'image_path' => $masterItem['image_link'] ?? '',
                        'total' => 0,
                        'available' => 0,
                        'unit_of_measure' => $masterItem['uom'] ?? 'Pcs'
                    ];
                }
            } else {
                // Fallback check: Search inventory_requests for item_name snapshot
                $stmtSnap = $conn->prepare("SELECT item_name, uom FROM inventory_requests WHERE inventory_id = ? LIMIT 1");
                $stmtSnap->execute([(int)$inventory_id]);
                $snap = $stmtSnap->fetch(PDO::FETCH_ASSOC);
                if ($snap) {
                    $item = [
                        'id' => $inventory_id,
                        'name' => $snap['item_name'],
                        'category' => 'Request Record',
                        'type' => 'Reusable',
                        'isConsumable' => 0,
                        'total' => 0,
                        'available' => 0,
                        'unit_of_measure' => $snap['uom'] ?? 'Pcs'
                    ];
                }
            }
        }

        if ($item && empty($item['image_path']) && !empty($item['item_image_fallback'])) {
            $item['image_path'] = $item['item_image_fallback'];
        }

        $itemName = $item['name'] ?? '';
        $actualInvId = $item['id'] ?? $inventory_id;

        // Fetch requests by inventory_id OR item_name
        $stmt = $conn->prepare("
            SELECT * FROM inventory_requests 
            WHERE inventory_id = ? OR (item_name = ? AND item_name != '')
            ORDER BY id DESC
        ");
        $stmt->execute([(int)$actualInvId, $itemName]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch damage logs by inventory_id OR item_name
        $stmt2 = $conn->prepare("
            SELECT * FROM damage_log 
            WHERE inventory_id = ? OR (item_name = ? AND item_name != '')
            ORDER BY id DESC
        ");
        $stmt2->execute([(int)$actualInvId, $itemName]);
        $damage_logs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return [
            'item' => $item ?: null,
            'requests' => $requests,
            'damage_logs' => $damage_logs
        ];
    } catch (PDOException $e) {
        error_log('getInventoryHistory error: ' . $e->getMessage());
        return false;
    }
}
?>
