<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "mycokhan";
$dbname = "joval_microfinance";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all applications
$sql = "SELECT * FROM loan_applications ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Stats
$totalApplications = $conn->query("SELECT COUNT(*) as total FROM loan_applications")
    ->fetch_assoc()['total'] ?? 0;

$pendingCount = $conn->query("SELECT COUNT(*) as count FROM loan_applications WHERE status='pending'")
    ->fetch_assoc()['count'] ?? 0;

$approvedCount = $conn->query("SELECT COUNT(*) as count FROM loan_applications WHERE status='approved'")
    ->fetch_assoc()['count'] ?? 0;

$rejectedCount = $conn->query("SELECT COUNT(*) as count FROM loan_applications WHERE status='rejected'")
    ->fetch_assoc()['count'] ?? 0;

$totalDisbursed = $conn->query("SELECT SUM(loan_amount) as total FROM loan_applications WHERE status='approved'")
    ->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Joval Microfinance Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary:#1a5f4a;
    --dark:#264653;
    --light:#f8f9fa;
    --white:#fff;
    --gray:#6c757d;
    --shadow:0 5px 20px rgba(0,0,0,0.1);
}

*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins;}

body{background:#f0f2f5;}

/* SIDEBAR */
.sidebar{
    position:fixed;
    width:260px;
    height:100vh;
    background:linear-gradient(135deg,#1a5f4a,#2d8a6e);
    padding:20px;
}

/* LOGO SECTION */
.logo-section{
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:30px;
    padding:15px;
}

.logo-img{
    width:180px;
    height:60px;
    object-fit:contain;
    border-radius:8px;
    filter:brightness(1.1) contrast(1.05);
}

.sidebar-menu a{
    display:flex;
    gap:10px;
    padding:12px;
    color:#007bff;
    text-decoration:none;
    border-radius:8px;
}

.sidebar-menu a:hover,.sidebar-menu a.active{background:rgba(255,255,255,0.15);}

.sidebar-menu a i{width:20px;}

/* MAIN */
.main-content{
    margin-left:260px;
    padding:20px;
}

/* STATS */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:25px;
}

.stat-card{
    background:white;
    padding:20px;
    border-radius:16px;
    box-shadow:var(--shadow);
    display:flex;
    align-items:center;
    gap:15px;
    transition:transform 0.3s,box-shadow 0.3s;
    border-left:5px solid transparent;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 25px rgba(0,0,0,0.15);
}

.stat-card.total{
    border-left-color:#6366f1;
    background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);
}
.stat-card.total .stat-icon{background:rgba(255,255,255,0.2);color:white;}
.stat-card.total .stat-label,.stat-card.total .stat-value{color:white;}

.stat-card.pending{
    border-left-color:#f59e0b;
    background:linear-gradient(135deg,#f59e0b 0%,#fbbf24 100%);
}
.stat-card.pending .stat-icon{background:rgba(255,255,255,0.2);color:white;}
.stat-card.pending .stat-label,.stat-card.pending .stat-value{color:white;}

.stat-card.approved{
    border-left-color:#10b981;
    background:linear-gradient(135deg,#10b981 0%,#34d399 100%);
}
.stat-card.approved .stat-icon{background:rgba(255,255,255,0.2);color:white;}
.stat-card.approved .stat-label,.stat-card.approved .stat-value{color:white;}

.stat-card.rejected{
    border-left-color:#ef4444;
    background:linear-gradient(135deg,#ef4444 0%,#f87171 100%);
}
.stat-card.rejected .stat-icon{background:rgba(255,255,255,0.2);color:white;}
.stat-card.rejected .stat-label,.stat-card.rejected .stat-value{color:white;}

.stat-card.disbursed{
    border-left-color:#0ea5e9;
    background:linear-gradient(135deg,#0ea5e9 0%,#38bdf8 100%);
}
.stat-card.disbursed .stat-icon{background:rgba(255,255,255,0.2);color:white;}
.stat-card.disbursed .stat-label,.stat-card.disbursed .stat-value{color:white;}

.stat-icon{
    width:55px;
    height:55px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.5rem;
    background:#f3f4f6;
    color:#6366f1;
}

.stat-info{
    display:flex;
    flex-direction:column;
}

.stat-label{
    font-size:0.85rem;
    color:#6b7280;
    font-weight:500;
}

.stat-value{
    font-size:1.6rem;
    font-weight:700;
    color:#1f2937;
}

/* TABLE FIX (IMPORTANT PART) */
.table-container{
    background:white;
    border-radius:10px;
    box-shadow:var(--shadow);
    overflow-x:auto;   /* 🔥 FIX FOR ACTION COLUMN */
}

table{
    width:100%;
    min-width:950px;   /* 🔥 FORCE SCROLL SO ACTION IS VISIBLE */
    border-collapse:collapse;
}

th,td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid #eee;
}

th{
    background:#f8f9fa;
}

/* STATUS */
.status{
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
}

.status.pending{background:#fff3cd;color:#856404;}
.status.approved{background:#d4edda;color:#155724;}
.status.rejected{background:#f8d7da;color:#721c24;}

/* BUTTON FIX */
.action-btn{
    padding:6px 12px;
    background:#1a5f4a;
    color:white !important;
    border-radius:6px;
    text-decoration:none;
    display:inline-block;
}

/* MOBILE */
@media(max-width:768px){
    .sidebar{position:relative;width:100%;height:auto;}
    .main-content{margin-left:0;}
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo-section">
        <div class="logo-icon">
            <img src="JOVAL MICROFINANCE.bmp" alt="Joval Microfinance Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="logo-text">
            <span class="logo-main">Joval</span>
            <span class="logo-sub">Microfinance</span>
        </div>
    </div>
    <div class="sidebar-menu">
        <a href="#" class="active"><i class="fas fa-home"></i>Dashboard</a>
        <a href="#"><i class="fas fa-file"></i>Applications</a>
        <a href="#"><i class="fas fa-users"></i>Customers</a>
        <a href="index.html"><i class="fas fa-globe"></i>Website</a>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">

<h2>Loan Applications</h2>
<p><?= date('F j, Y') ?></p>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Applications</span>
            <span class="stat-value"><?= $totalApplications ?></span>
        </div>
    </div>
    <div class="stat-card pending">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Pending Review</span>
            <span class="stat-value"><?= $pendingCount ?></span>
        </div>
    </div>
    <div class="stat-card approved">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Approved</span>
            <span class="stat-value"><?= $approvedCount ?></span>
        </div>
    </div>
    <div class="stat-card rejected">
        <div class="stat-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Rejected</span>
            <span class="stat-value"><?= $rejectedCount ?></span>
        </div>
    </div>
    <div class="stat-card disbursed">
        <div class="stat-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Disbursed</span>
            <span class="stat-value">TSh <?= number_format($totalDisbursed) ?></span>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="table-container">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Phone Number</th>
    <th>Location</th>
    <th>Guarantor</th>
    <th>Amount</th>
    <th>Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>

<tr>
    <td>#<?= str_pad($row['id'],4,'0',STR_PAD_LEFT) ?></td>

    <td>
        <?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?>
    </td>
    <td><?= htmlspecialchars($row['phone_number']) ?></td>

    <td><?= htmlspecialchars($row['residential_location']) ?></td>

    <td><?= htmlspecialchars($row['guarantor_name']) ?></td>

    <td><b>TSh <?= number_format($row['loan_amount']) ?></b></td>

    <td><?= date('M j, Y',strtotime($row['created_at'])) ?></td>

    <td>
        <span class="status <?= $row['status'] ?>">
            <?= ucfirst($row['status'] ?? 'pending') ?>
        </span>
    </td>

    <!-- 🔥 ACTION FIXED -->
    <td>
        <a href="view_application.php?id=<?= $row['id'] ?>" class="action-btn">
            View
        </a>
    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>

<tr>
<td colspan="8" style="text-align:center;padding:30px;">
    No applications found
</td>
</tr>

<?php endif; ?>
</tbody>
</table>
</div>

</div>

</body>
</html>

<?php $conn->close(); ?>