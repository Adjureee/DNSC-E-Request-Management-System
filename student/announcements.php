<?php
require_once '../config.php';
checkStudentAuth();

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM announcements");
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$stmt = $conn->prepare("SELECT id, title, start_date, end_date FROM announcements ORDER BY start_date DESC LIMIT ?, ?");
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .announcement-card {
            margin-bottom: 20px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.05);
            background-color: #fff;
            transition: box-shadow 0.3s ease;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .announcement-card:hover {
            box-shadow: 0 6px 15px rgb(0 0 0 / 0.15);
        }
        .announcement-info {
            flex-grow: 1;
        }
        .announcement-title {
            margin: 0;
            font-size: 1.25rem;
            color: #198754;
            font-weight: 600;
        }
        .announcement-dates {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .btn-view {
            white-space: nowrap;
        }
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
        .btn-success {
            background-color: #498428;
            border-color: #498428;
        }
        .btn-success:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-outline-primary {
            color: #498428;
            border-color: #498428;
        }
        .btn-outline-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
            color: white;
        }
        .card-header {
            background-color: #e9f7ef;
            border-bottom: 1px solid #d1e7dd;
        }
        .page-link {
            color: #498428;
        }
        .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
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
                        <h5>Student Portal</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="announcement.php">
                                <i class="fas fa-bullhorn me-2"></i> Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="new_request.php">
                                <i class="fas fa-plus-circle me-2"></i> New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_requests.php">
                                <i class="fas fa-clipboard-list me-2"></i> My Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell me-2"></i> Notifications
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
<main class="col-12 px-md-4 py-4" id="mainContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2">Announcement</h1>
                </div>
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info text-center">No announcements found.</div>
    <?php else: ?>
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-card">
                <div class="announcement-info">
                    <h2 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                    <small class="announcement-dates">
                        Active: <?= htmlspecialchars($announcement['start_date']) ?> to <?= htmlspecialchars($announcement['end_date']) ?>
                    </small>
                </div>
                <button class="btn btn-success btn-sm btn-view" 
                    data-id="<?= $announcement['id'] ?>" 
                    onclick="loadAnnouncementDetails(this)">View Details</button>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>
</div>

<!-- Modal for Announcement Details -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-lg rounded-4 border-0">
      <div class="modal-header bg-success text-white rounded-top-4">
        <h5 class="modal-title fw-semibold" id="announcementModalLabel">Announcement Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h3 id="modal-title" class="text-success fw-bold"></h3>
        <p class="text-muted mb-3" id="modal-dates"></p>
        <img id="modal-photo" src="" alt="Announcement Image" class="img-fluid mb-3 rounded shadow-sm" style="display:none; max-height: 400px; object-fit: contain;">
        <p id="modal-body-text" class="lh-lg"></p>
      </div>
      <div class="modal-footer bg-light rounded-bottom-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function loadAnnouncementDetails(button) {
    const id = button.getAttribute('data-id');
    try {
        const response = await fetch('fetch_announcement.php?id=' + id);
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();

        if (data.success) {
            document.getElementById('modal-title').textContent = data.announcement.title;
            document.getElementById('modal-dates').textContent = `Active: ${data.announcement.start_date} to ${data.announcement.end_date}`;
            const photoEl = document.getElementById('modal-photo');
            if(data.announcement.photo) {
                photoEl.src = '../' + data.announcement.photo;
                photoEl.style.display = 'block';
            } else {
                photoEl.style.display = 'none';
            }
            document.getElementById('modal-body-text').innerHTML = data.announcement.body.replace(/\n/g, '<br>');

            const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
            modal.show();
        } else {
            alert('Announcement not found.');
        }
    } catch (error) {
        alert('Failed to fetch announcement details.');
        console.error(error);
    }
}
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
