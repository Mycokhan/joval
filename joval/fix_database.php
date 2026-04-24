<?php
// Fix database - add status column if it doesn't exist

$servername = "localhost";
$username = "root";
$password = "mycokhan";
$dbname = "joval_microfinance";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if status column exists
$result = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'status'");

if ($result->num_rows == 0) {
    // Add status column
    $sql = "ALTER TABLE loan_applications ADD COLUMN status ENUM('pending','approved','rejected','under_review') DEFAULT 'pending' AFTER id_document";
    
    if ($conn->query($sql)) {
        echo "✅ Status column added successfully!";
    } else {
        echo "❌ Error adding status column: " . $conn->error;
    }
} else {
    echo "✅ Status column already exists!";
}

// Check if notes column exists
$result = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'notes'");

if ($result->num_rows == 0) {
    // Add notes column
    $sql = "ALTER TABLE loan_applications ADD COLUMN notes TEXT DEFAULT NULL AFTER status";
    
    if ($conn->query($sql)) {
        echo "<br>✅ Notes column added successfully!";
    } else {
        echo "<br>❌ Error adding notes column: " . $conn->error;
    }
} else {
    echo "<br>✅ Notes column already exists!";
}

$conn->close();
?>

<p><a href="admin.php">Go to Admin Panel</a></p>