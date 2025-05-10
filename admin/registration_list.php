<?php
require_once '../config.php';
checkAdminAuth();

// Fetch registration data
$sql = "SELECT id, stud_id, full_name, email, verification_status FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Check for success or error status in the URL
$statusMessage = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'approved') {
        $statusMessage = 'Registration successfully approved.';
    } elseif ($_GET['status'] == 'rejected') {
        $statusMessage = 'Registration successfully rejected.';
    } elseif ($_GET['status'] == 'error') {
        $statusMessage = 'There was an error processing the registration.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration List - DNSC E-Request System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #198754;
            color: white;
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            position: relative;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.2);
        }
        .badge-notification {
            position: absolute;
            top: 5px;
            right: 15px;
            background-color: red;
            color: white;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 50%;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-action {
            min-width: 100px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h5>DNSC E-Request System</h5>
                    <p class="text-muted">Admin Panel</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="requests.php"><i class="fas fa-clipboard-list me-2"></i> All Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending.php"><i class="fas fa-clock me-2"></i> Pending Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="approved.php"><i class="fas fa-check-circle me-2"></i> Approved Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="completed.php"><i class="fas fa-check-double me-2"></i> Completed Requests</a></li>
                    <li class="nav-item"><a class="nav-link active" href="registration_list.php"><i class="fas fa-user-check me-2"></i> Registration List</a></li>
                    <li class="nav-item mt-5"><a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4">Registration List</h2>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" id="userSearch" class="form-control" placeholder="Search by name, ID or email...">
                        <button class="btn btn-outline-success" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($statusMessage): ?>
                <div class="alert alert-info"><?= $statusMessage ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['stud_id']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <?php
                                        $status = $row['verification_status'];
                                        if ($status == 'approved_student') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        } elseif ($status == 'approved_alumni') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        }  elseif ($status == 'rejected') {
                                            echo '<span class="badge bg-danger">Rejected</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_registration.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-action">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No registrations found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('table tbody tr');
        
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if(rowText.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>
