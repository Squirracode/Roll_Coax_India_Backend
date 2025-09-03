<?php
require_once 'backupFunctions.php';
require 'packages/vendor/autoload.php'; // Ensure to include Composer autoload file

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

// Function to send the Excel file as an email attachment
function sendFileAsEmail($filePath, $recipientEmail) {
    
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pokenikunj@gmail.com';
        $mail->Password = '9033734886nik';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('pokenikunj@gmail.com', 'Nikunj Jasani');
        $mail->addAddress($recipientEmail);

        // Attachments
        $mail->addAttachment($filePath);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Backup File';
        $mail->Body    = 'Please find the backup file attached.';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$e}";
    }
}

// Get the requested endpoint
$endpoint = $_GET['endpoint'] ?? '';

// Check if the endpoint is for fetching user details
if ($endpoint === 'getBackup') {
    // Authenticate the user
    $authenticated = authenticateUser();
    if ($authenticated) {
        
        // Fetch user details
        $backupDetails = getBackup();
        if ($backupDetails) {
            // Generate Excel file
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($backupDetails, null, 'A1');

            $writer = new Xls($spreadsheet);
            $filePath = 'backup_' . date('Ymd_His') . '.xls';
            $writer->save($filePath);
            
            // Send the file as an email attachment
            sendFileAsEmail($filePath, 'pokenikunj@gmail.com');
            
            // Return JSON response with upload success
            header('Content-Type: application/json');
            echo json_encode(array("message" => "Backup file uploaded successfully"));
           
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "User not found"));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Unauthorized"));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Endpoint not found"));
}
?>
