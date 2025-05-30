<?php
require_once '../config.php';
checkAlumniAuth();

$user_id = $_SESSION['user_id'];


if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    redirect('notifications.php');
}


if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    redirect('notifications.php');
}


if (isset($_GET['delete_all']) && $_GET['delete_all'] === 'read') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    redirect('notifications.php');
}


$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ?, ?
");
$stmt->bind_param("iii", $user_id, $start, $limit);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
   .sidebar {
            min-height: 100vh;
            background-color: #2d5516;
           color: white;
        }
        .custom-topbar {
         background-color: #2d5516;
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
        .btn-primary {
            background-color: #498428;
            border-color: #498428;
        }
    .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
.btn-outline-success {
    color: #498428;
    border-color: #498428;
}
.btn-outline-success:hover {
    background-color: #2d5516;
    border-color: #2d5516;
    color: white;
}
.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
}
.btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
}
.alert-info {
    background-color: #d1e7dd;
    border-color: #badbcc;
    color: #0f5132;
}
.badge.bg-success {
    background-color: #2d5516 !important;
}
.page-link {
            color: #498428;
        }
.page-item.active .page-link {
    background-color: #2d5516;
    border-color: #2d5516;
}
   .alert-info {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #2d5516;
        }
    </style>
</head>
<body>
    <!-- Topbar/Header -->
<nav class="navbar navbar-expand-lg navbar-dark custom-topbar px-3">
  <div class="container-fluid d-flex justify-content-between align-items-center">
        
        <!-- Left section: Brand + Toggle -->
        <div class="d-flex align-items-center">
            <!-- Sidebar toggle -->
            <button class="btn btn-outline-light me-2" id="sidebarToggleTop">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand mb-0 h1" href="#">DNSC E-Request System</a>
        </div>

    <!-- Profile dropdown -->
    <div class="dropdown ms-auto d-none d-lg-block">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
     <!-- Profile dropdown for smaller screens (visible when navbar is collapsed) -->
     <div class="dropdown d-block d-lg-none">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid" id="layoutRow">
    <div class="row">
        <!-- Sidebar -->
      <div class="col-md-3 col-lg-2 sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5>Alumni Portal</h5>
                       
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="new_request.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_requests.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                My Requests
                            </a>
                        </li>
                    <li class="nav-item">
                        <a class="nav-link active position-relative" href="notifications.php">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                            <a class="nav-link" href="contact.php">
                                <i class="fas fa-envelope me-2">
                                </i> Contact Admin
                            </a>
                        </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-12 px-md-4 py-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h2">Notifications</h1>
                <div>
                    <?php if ($unread_count > 0): ?>
                        <a href="?mark_read=all" class="btn btn-outline-success btn-sm me-2">Mark All as Read</a>
                    <?php endif; ?>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteAllModal">Delete All Read</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <strong>All Notifications</strong>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-success"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="p-4 text-center text-muted">You don't have any notifications yet.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                                     <div>
                                        <h6>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-success me-2">New</span>
                                            <?php endif; ?>
                                            <?php if (strpos($notification['message'], 'Reply from Admin') === 0): ?>
                                                Admin Response
                                            <?php else: ?>
                                                Request Update
                                            <?php endif; ?>
                                        </h6>

                                        <?php if (strpos($notification['message'], 'Reply from Admin') === 0): ?>
                                            <div class="p-3 rounded border bg-light">
                                                <?php
                                                    // Remove the "Reply from Admin" line and preserve formatting
                                    $replyBody = preg_replace('/^Reply from Admin\s*/', '', $notification['message']);
                                    $replyBody = preg_replace('/ID:\s*\d+.*$/m', '', $replyBody);

                                                echo nl2br($replyBody);
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php endif; ?>

                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                        </small>

                                    <?php
                                    // Only show view request button if it's NOT an admin response and contains an ID
                                    if (strpos($notification['message'], 'Reply from Admin') !== 0 &&
                                        preg_match('/ID:\s*(\d+)/', $notification['message'], $matches)) {
                                        echo '<br><a href="view_request.php?id=' . $matches[1] . '" class="btn btn-sm btn-outline-primary mt-2 view-request" data-notification-id="' . $notification['id'] . '">View Request</a>';
                                    }
                                    ?>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal<?php echo $notification['id']; ?>">Delete</button>

                                        <!-- Delete Single Modal -->
                                        <div class="modal fade" id="confirmDeleteModal<?php echo $notification['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-sm modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete this notification?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="?delete=<?php echo $notification['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav class="d-flex justify-content-center">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Delete All Modal -->
<div class="modal fade" id="confirmDeleteAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete All Read</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete all read notifications?
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <a href="?delete_all=read" class="btn btn-danger btn-sm">Delete All</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('.view-request').on('click', function (e) {
        e.preventDefault();
        const button = $(this);
        const id = button.data('notification-id');
        const href = button.attr('href');

        $.post('mark_read_single.php', { id: id }, function () {
            window.location.href = href;
        });
    });
});
</script>
<script>
      document.getElementById('sidebarToggleTop').addEventListener('click', function () {
        var sidebar = document.getElementById('sidebarMenu');
        var main = document.getElementById('mainContent');

        sidebar.classList.toggle('show');

        if (sidebar.classList.contains('show')) {
            main.classList.remove('col-12');
            main.classList.add('col-md-9', 'col-lg-10');
        } else {
            main.classList.remove('col-md-9', 'col-lg-10');
            main.classList.add('col-12');
        }
    });
</script>
</body>
</html>
