<?php
require_once '../config.php';
checkAdminAuth();

// Fetch all pending requests (student + alumni)
$stmt = $conn->prepare("
    SELECT r.id, r.user_id, r.request_type, r.status, r.is_seen, r.created_at, u.full_name, 'student' AS role
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.status = 'pending'
    
    UNION ALL
    
    SELECT ar.id, ar.user_id, ar.request_type, ar.status, ar.is_seen, ar.created_at, u.full_name, 'alumni' AS role
    FROM alumni_requests ar
    JOIN users u ON ar.user_id = u.id
    WHERE ar.status = 'pending'
    
    ORDER BY created_at DESC
");
$stmt->execute();
$pendingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count unseen student + alumni requests
$countStmt = $conn->prepare("
    SELECT (
        (SELECT COUNT(*) FROM requests WHERE status = 'pending' AND is_seen = 0) +
        (SELECT COUNT(*) FROM alumni_requests WHERE status = 'pending' AND is_seen = 0)
    ) AS unseen_count
");
$countStmt->execute();
$unseenCount = $countStmt->get_result()->fetch_assoc()['unseen_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pending Requests — Admin</title>
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
    }
    .nav-link:hover {
      color: white;
    }
    .nav-link.active {
      color: white;
      background-color: rgba(255,255,255,.2);
    }
    .dashboard-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-primary {
      background-color: #498428;
      border-color: #498428;
    }
    .btn-primary:hover {
      background-color: #2d5516;
      border-color: #2d5516;
    }
    .sidebar .nav-link {
      position: relative;
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
  </style>
</head>
<body>  
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
      <div class="position-sticky pt-3">
        <div class="text-center mb-4">
          <h5>DNSC E-Request System</h5>
          <p class="text-white">Admin Panel</p>
        </div>
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="fas fa-tachometer-alt me-2"></i>
              Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="requests.php">
              <i class="fas fa-clipboard-list me-2"></i>
              All Requests
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="pending.php">
             <i class="fas fa-clock me-2"></i>
             Pending Requests
              <?php if($unseenCount): ?>
                <span class="badge-notification"><?php echo $unseenCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="approved.php">
              <i class="fas fa-check-circle me-2"></i>
              Approved Requests
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="completed.php">
              <i class="fas fa-check-double me-2"></i>
              Completed Requests
            </a>
          </li>
          <li class="nav-item">
                        <a class="nav-link" href="registration_list.php">
                            <i class="fas fa-user-check me-2"></i> Registration List
                        </a>
                    </li>
           <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>
                    </li>          
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between align-items-center mb-3 border-bottom">
        <h1 class="h2">Pending Requests</h1>
        <span class="btn btn-sm btn-outline-secondary">
          Welcome, <?php echo $_SESSION['full_name']; ?>
        </span>
      </div>

      <div class="card dashboard-card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Pending Requests</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pendingRequests)): ?>
                  <tr><td colspan="7" class="text-center py-4">No pending requests found</td></tr>
                <?php else: ?>
                  <?php foreach ($pendingRequests as $r): ?>
                    <tr>
                      <td>
                        <?php echo $r['id']; ?>
                        <?php if (!$r['is_seen']): ?>
                          <span class="badge bg-danger ms-1">New</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                      <td><?php echo htmlspecialchars($r['request_type']); ?></td>
                      <td><span class="badge bg-warning"><?php echo ucfirst($r['status']); ?></span></td>
                      <td><?php echo date('M d, Y g:i A', strtotime($r['created_at'])); ?></td>
                      <td><?php echo ucfirst($r['role']); ?></td>
                      <td>
                        <a href="view_request.php?id=<?php echo $r['id']; ?>&role=<?php echo $r['role']; ?>" class="btn btn-sm btn-primary">View</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
