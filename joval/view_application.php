<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "mycokhan";
$dbname = "joval_microfinance";
// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = $_POST['application_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $adminNotes = $_POST['notes'] ?? '';
    
    // Also check for button names as fallback
    if (empty($action) && isset($_POST['approve_btn'])) {
        $action = 'approved';
    } elseif (empty($action) && isset($_POST['reject_btn'])) {
        $action = 'rejected';
    }

    if ($applicationId && in_array($action, ['approved', 'rejected'])) {
        // Check if status column exists
        $colCheck = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'status'");
        
        if ($colCheck->num_rows > 0) {
            $status = $action;
            // Check if updated_at column exists
            $updatedAtCheck = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'updated_at'");
            if ($updatedAtCheck->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE loan_applications SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $status, $adminNotes, $applicationId);
            } else {
                $stmt = $conn->prepare("UPDATE loan_applications SET status = ?, notes = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $adminNotes, $applicationId);
            }
        } else {
            // If status column doesn't exist, use a workaround with a separate table or just update notes
            $stmt = $conn->prepare("UPDATE loan_applications SET notes = CONCAT(IFNULL(notes,''), ? ) WHERE id = ?");
            $noteText = "\n[" . strtoupper($action) . "] " . $adminNotes . " - " . date('Y-m-d H:i:s');
            $stmt->bind_param("si", $noteText, $applicationId);
        }
        
        if ($stmt->execute()) {
            $message = $action === 'approved' 
                ? "Loan application approved! (Note added)" 
                : "Loan application rejected! (Note added)";
            $messageType = 'success';
            
            // Refresh application data after update
            $stmt2 = $conn->prepare("SELECT * FROM loan_applications WHERE id = ?");
            $stmt2->bind_param("i", $applicationId);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $application = $result->fetch_assoc();
            $stmt2->close();
            
            // Update fullName variable
            if ($application) {
                $fullName = $application['first_name'] . ' ' . ($application['middle_name'] ? $application['middle_name'] . ' ' : '') . $application['last_name'];
            }
        } else {
            $message = "Error updating application.";
            $messageType = 'error';
        }
    }
}

// Get application details
$application = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM loan_applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    $stmt->close();
}

if (!$application) {
    header("Location: admin.php");
    exit;
}

$fullName = $application['first_name'] . ' ' . ($application['middle_name'] ? $application['middle_name'] . ' ' : '') . $application['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - Joval Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a5f4a;
            --primary-dark: #134436;
            --primary-light: #2d8a6e;
            --secondary: #f4a261;
            --accent: #e76f51;
            --success: #2a9d8f;
            --danger: #dc3545;
            --dark: #264653;
            --light: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
            --shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f0f2f5;
            color: var(--dark);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #1a5f4a 0%, #2d8a6e 100%);
            padding: 2rem 1.5rem;
            z-index: 1000;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 3rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 55px;
            height: 55px;
        }

        .logo-text {
            color: var(--white);
            font-size: 1.4rem;
            font-weight: 700;
        }

        .logo-text span {
            color: var(--secondary);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: #007bff;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            opacity: 0.8;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(0, 123, 255, 0.3);
            opacity: 1;
        }

        .sidebar-menu a i {
            width: 20px;
            color: #007bff;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--dark);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: var(--primary-dark);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Application Details */
        .detail-container {
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .detail-header {
            background: linear-gradient(135deg, #1a5f4a 0%, #2d8a6e 100%);
            padding: 2rem;
            color: var(--white);
        }

        .detail-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .detail-header .application-id {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .detail-body {
            padding: 2rem;
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .detail-section h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .detail-item label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }

        .detail-item span {
            font-size: 1rem;
            color: var(--dark);
        }

        .amount-display {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .status-display {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-display.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-display.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-display.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-display.under_review {
            background: #cce5ff;
            color: #004085;
        }

        /* Action Form */
        .action-form {
            background: var(--light);
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .action-form h3 {
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            min-height: 100px;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve {
            background: var(--success);
            color: var(--white);
        }

        .btn-approve:hover {
            background: #218c74;
        }

        .btn-reject {
            background: var(--danger);
            color: var(--white);
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .btn-cancel {
            background: var(--gray);
            color: var(--white);
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        /* Already Processed */
        .already-processed {
            background: var(--light);
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
            text-align: center;
        }

        .already-processed h3 {
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .already-processed .status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="admin.php" class="sidebar-logo">
            <div class="logo-icon">
                 <img src="JOVAL MICROFINANCE.bmp" alt="Joval Microfinance Logo" style="width: 100%; height: 100%; object-fit: contain;">
                    <rect width="100" height="100" rx="20" fill="white"/>
                    <path d="M20 70V30L50 45L80 30V70L50 55L20 70Z" fill="#1a5f4a"/>
                    <path d="M50 45V60" stroke="#f4a261" stroke-width="5" stroke-linecap="round"/>
                    <circle cx="50" cy="20" r="5" fill="#f4a261"/>
                </svg>
            </div>
            <div class="logo-text">Joval <span>Admin</span></div>
        </a>
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fas fa-home" style="color: #007bff;"></i> Dashboard</a></li>
            <li><a href="admin.php"><i class="fas fa-file-alt" style="color: #007bff;"></i> Applications</a></li>
            <li><a href="#"><i class="fas fa-users" style="color: #007bff;"></i> Customers</a></li>
            <li><a href="#"><i class="fas fa-cog" style="color: #007bff;"></i> Settings</a></li>
            <li><a href="index.html"><i class="fas fa-external-link-alt" style="color: #007bff;"></i> View Website</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Application Details</h1>
            <a href="admin.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
        <div class="alert <?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Application Details -->
        <div class="detail-container">
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($fullName); ?></h2>
                <div class="application-id">Application #<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="detail-body">
                <div class="detail-section">
                    <h3>Personal Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span><?php echo htmlspecialchars($fullName); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Residential Location</label>
                            <span><?php echo htmlspecialchars($application['residential_location']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Guarantor Name</label>
                            <span><?php echo htmlspecialchars($application['guarantor_name']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Loan Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Loan Amount</label>
                            <span class="amount-display">TSh <?php echo number_format($application['loan_amount']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Current Status</label>
                            <span class="status-display <?php echo $application['status'] ?? 'pending'; ?>">
                                <?php 
                                // Check if status column exists
                                $colCheck = $conn->query("SHOW COLUMNS FROM loan_applications LIKE 'status'");
                                if ($colCheck->num_rows > 0) {
                                    echo ucfirst(str_replace('_', ' ', $application['status'] ?? 'pending'));
                                } else {
                                    // Check notes for approval/rejection
                                    $notes = $application['notes'] ?? '';
                                    if (stripos($notes, '[APPROVED]') !== false) {
                                        echo 'Approved';
                                    } elseif (stripos($notes, '[REJECTED]') !== false) {
                                        echo 'Rejected';
                                    } else {
                                        echo 'Pending';
                                    }
                                }
                                ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Application Date</label>
                            <span><?php echo date('F j, Y g:i A', strtotime($application['created_at'])); ?></span>
                        </div>
                        <?php if (!empty($application['notes'])): ?>
                        <div class="detail-item">
                            <label>Admin Notes</label>
                            <span><?php echo htmlspecialchars($application['notes']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Form -->
                <?php 
                // Get current status
                $currentStatus = $application['status'] ?? 'pending';
                $notes = $application['notes'] ?? '';
                ?>
                <div class="action-form">
                    <h3>Process Application</h3>
                    <form method="POST" action="" id="processForm" onsubmit="return validateAction();">
                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                        
                        <div class="form-group">
                            <label for="notes">Admin Notes (Optional)</label>
                            <textarea name="notes" id="notes" placeholder="Add any notes about this application..."></textarea>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="approve_btn" class="btn btn-approve" onclick="document.getElementById('action').value='approved'; return true;">
                                <i class="fas fa-check"></i> Approve Loan
                            </button>
                            <button type="submit" name="reject_btn" class="btn btn-reject" onclick="document.getElementById('action').value='rejected'; return true;">
                                <i class="fas fa-times"></i> Reject Loan
                            </button>
                        </div>
                        <input type="hidden" name="action" id="action" value="">
                    </form>
                    <p id="actionError" style="color: red; display: none; margin-top: 10px;">Please select an action (Approve or Reject)</p>
                </div>

                <script>
                function validateAction() {
                    var action = document.getElementById('action').value;
                    if (!action) {
                        document.getElementById('actionError').style.display = 'block';
                        return false;
                    }
                    document.getElementById('actionError').style.display = 'none';
                    return true;
                }
                </script>
            </div>
        </div>
    </main>
</body>
</html>

<?php
$conn->close();
?>