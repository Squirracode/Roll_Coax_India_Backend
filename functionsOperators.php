<?php
require_once __DIR__ . '/functions.php';

function getAllOperators() {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->query("SELECT * FROM operators ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function addOperator($name, $email = null) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO operators (name, email) VALUES (:name, :email)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function updateOperator($id, $name, $email = null) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch existing operator details to check for name changes
        $stmtOld = $pdo->prepare("SELECT name FROM operators WHERE id = :id");
        $stmtOld->execute([':id' => $id]);
        $oldOp = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $oldName = $oldOp ? trim($oldOp['name']) : '';

        // Update operator in operators table
        $stmt = $pdo->prepare("UPDATE operators SET name = :name, email = :email WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $result = $stmt->execute();

        // If name changed and oldName was not empty, update assigned items in inventory_items
        if ($result && !empty($oldName) && strcasecmp($oldName, trim($name)) !== 0) {
            $newName = trim($name);
            $itemsStmt = $pdo->query("SELECT id, operators FROM inventory_items WHERE operators IS NOT NULL AND operators != ''");
            $allInventory = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $updateItemStmt = $pdo->prepare("UPDATE inventory_items SET operators = :operators WHERE id = :id");

            foreach ($allInventory as $inv) {
                $rawOps = $inv['operators'];
                if (empty($rawOps)) continue;

                $opsArray = json_decode($rawOps, true);
                if (is_array($opsArray)) {
                    $modified = false;
                    foreach ($opsArray as $idx => $opName) {
                        if (strcasecmp(trim($opName), $oldName) === 0) {
                            $opsArray[$idx] = $newName;
                            $modified = true;
                        }
                    }
                    if ($modified) {
                        $updateItemStmt->execute([
                            ':operators' => json_encode(array_values($opsArray)),
                            ':id' => $inv['id']
                        ]);
                    }
                } else if (is_string($rawOps) && strpos($rawOps, $oldName) !== false) {
                    $newOpsStr = str_ireplace($oldName, $newName, $rawOps);
                    $updateItemStmt->execute([
                        ':operators' => $newOpsStr,
                        ':id' => $inv['id']
                    ]);
                }
            }
        }

        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

function deleteOperator($id) {
    global $host, $dbName, $dbUname, $dbPass;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch existing operator details
        $stmtOld = $pdo->prepare("SELECT name FROM operators WHERE id = :id");
        $stmtOld->execute([':id' => $id]);
        $oldOp = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $oldName = $oldOp ? trim($oldOp['name']) : '';

        // Delete from operators table
        $stmt = $pdo->prepare("DELETE FROM operators WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();

        // Remove operator reference from inventory_items if deleted
        if ($result && !empty($oldName)) {
            $itemsStmt = $pdo->query("SELECT id, operators FROM inventory_items WHERE operators IS NOT NULL AND operators != ''");
            $allInventory = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $updateItemStmt = $pdo->prepare("UPDATE inventory_items SET operators = :operators WHERE id = :id");

            foreach ($allInventory as $inv) {
                $rawOps = $inv['operators'];
                if (empty($rawOps)) continue;

                $opsArray = json_decode($rawOps, true);
                if (is_array($opsArray)) {
                    $filtered = array_values(array_filter($opsArray, function($opName) use ($oldName) {
                        return strcasecmp(trim($opName), $oldName) !== 0;
                    }));
                    if (count($filtered) !== count($opsArray)) {
                        $updateItemStmt->execute([
                            ':operators' => json_encode($filtered),
                            ':id' => $inv['id']
                        ]);
                    }
                }
            }
        }

        return $result;
    } catch (PDOException $e) {
        return false;
    }
}
