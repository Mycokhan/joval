<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Database configuration
$host="localhost";
$userrname = "localhost";
$username = "root";
$password = "mycokhan";
$dbname = "joval_microfinance";

// Create database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Create database if not exists


// Handle file upload
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$idDocumentPath = '';
if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
    $fileExt = strtolower(pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (in_array($fileExt, $allowedExts)) {
        $newFileName = uniqid('id_') . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['id_document']['tmp_name'], $targetPath)) {
            $idDocumentPath = $targetPath;
        }
    }
}

// Get form data
$firstName = $_POST['first_name'] ?? '';
$middleName = $_POST['middle_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$residentialLocation = $_POST['residential_location'] ?? '';
$guarantorName = $_POST['guarantor_name'] ?? '';
$loanAmount = $_POST['loan_amount'] ?? 0;

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($residentialLocation) || 
    empty($guarantorName) || empty($loanAmount)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields'
    ]);
    exit;
}

// Insert data into database
$stmt = $conn->prepare("INSERT INTO loan_applications 
    (first_name, middle_name, last_name, residential_location, guarantor_name, loan_amount, id_document) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssds", 
    $firstName, 
    $middleName, 
    $lastName, 
    $residentialLocation, 
    $guarantorName, 
    $loanAmount,
    $idDocumentPath
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!',
        'application_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting application: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>