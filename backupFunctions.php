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

function getBackup() {
    global $host, $dbName, $dbUname, $dbPass;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $dbUname, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->query("SELECT 
    q.`date` AS 'Quotation Date',
    q.`qutReference` AS 'Quotation#',
    q.`status` AS 'Status',
    CASE 
        WHEN q.`status` = 'Closed' THEN q.`reasonForClose`
        ELSE NULL
    END AS `reasonForClose`,
    c.`clientName` AS 'Client Name',
    a.`poDate` AS 'PO Date',
    a.`poNumber` AS 'PO#',
    a.`poFilePath` AS 'PO Path',
    a.`status` AS 'Work Status',
    t.`teamName` AS 'Team Name'
FROM 
    `quotation` q
JOIN 
    `clients` c ON q.`clientId` = c.`clientId`
LEFT JOIN 
    `assignables` a ON q.`qutId` = a.`qutId`
LEFT JOIN 
    (
        SELECT 
            ua1.`assignableId`,
            ua1.`teamId`
        FROM 
            `userAssignments` ua1
        JOIN 
            (
                SELECT 
                    ua2.`assignableId`, 
                    MAX(ua2.`assignDate`) AS latestDate
                FROM 
                    `userAssignments` ua2
                GROUP BY 
                    ua2.`assignableId`
            ) latest 
            ON ua1.`assignableId` = latest.`assignableId` AND ua1.`assignDate` = latest.latestDate
    ) lua ON a.`assignableId` = lua.`assignableId`
LEFT JOIN 
    `teamConfiguration` t ON lua.`teamId` = t.`teamId`
WHERE 
    q.`status` != 'Revised' AND q.`status` != 'Draft' OR q.`status` = 'Closed';
    ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $users; // Return the fetched users
    } catch (PDOException $e) {
        echo $e;
        // Handle database connection errors or query errors
        return false; // Return false if the query fails
    }
}



?>
