<?php
include 'connect.php';
if (
    empty($_POST['first_name']) ||
    empty($_POST['last_name']) ||
    empty($_POST['residential_location']) ||
    empty($_POST['guarantor_name']) ||
    empty($_POST['loan_amount']) ||
    empty($_FILES['id_document']['name']) ||
    empty($_POST['phone_number'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields'
    ]);
    exit;
}

// Get form data
$firstName = $_POST['first_name'];
$middleName = $_POST['middle_name'];
$lastName = $_POST['last_name'];
$residentialLocation = $_POST['residential_location'];
$guarantorName = $_POST['guarantor_name'];
$loanAmount = $_POST['loan_amount'] ;
$phoneNumber = $_POST['phone_number'];

// Handle file upload
$allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
$fileExt = strtolower(pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION));
if (in_array($fileExt, $allowedExts)) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = uniqid('id_') . '.' . $fileExt;
    $targetFile = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['id_document']['tmp_name'], $targetFile)) {
        $idDocumentPath = $targetFile;
    } else {
        die(json_encode([
            'success' => false,
            'message' => 'Error uploading file'
        ]));
    }
} else {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid file type'
    ]));
}

$sql="INSERT INTO loan_applications (first_name, middle_name, last_name, residential_location, guarantor_name, loan_amount, id_document, phone_number) VALUES ('$firstName', '$middleName', '$lastName', '$residentialLocation', '$guarantorName', $loanAmount, '$idDocumentPath', '$phoneNumber')";
if ($conn->query($sql) === TRUE) {
    echo json_encode([
        'success' => true,
        'message' => 'Loan application submitted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $sql . ' - ' . $conn->error
    ]);
}
$conn->close();

?>