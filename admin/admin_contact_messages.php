<?php
require_once '../config.php';
checkAdminAuth();

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total records count
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM contact_messages");
$totalRow = $totalQuery->fetch_assoc();
$total = (int)$totalRow['total'];
$total_pages = max(1, ceil($total / $limit));

// Fetch messages with pagination
$sql = "SELECT id, name, phone, role, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get dashboard notification variables from admin/dashboard.php
// Get system settings (triggers)
$settingsQuery = $conn->query("SELECT new_requests_count, new_registrations_count FROM system_settings WHERE id = 1");
$systemCounters = $settingsQuery->fetch_assoc();
$newRequestsCount = $systemCounters['new_requests_count'] ?? 0;
$newRegistrationsCount = $systemCounters['new_registrations_count'] ?? 0;

// Get admin notifs (triggers)
$adminNotificationsQuery = $conn->query("
    SELECT * FROM admin_notifications 
    WHERE is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$adminNotifications = $adminNotificationsQuery->fetch_all(MYSQLI_ASSOC);
$notificationCount = count($adminNotifications);

// Get notification summary by type (from triggers)
$notifTypeQuery = $conn->query("
    SELECT request_type, COUNT(*) as count
    FROM admin_notifications
    WHERE is_read = 0
    GROUP BY request_type
");
$notificationTypes = $notifTypeQuery->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Contact Messages - DNSC E-Request</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <style>
    body { background: #f8f9fa; 
    }
    .sidebar 
    {
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
    .message-card {
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 1rem;
      padding: 1rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .message-info {
      max-width: 80%;
    }
    .message-name {
      font-weight: 600;
      color: #198754;
      font-size: 1.1rem;
      margin-bottom: 0;
    }
    .message-date {
      font-size: 0.85rem;
      color: #6c757d;
    }
    .btn-primary {
      background-color: #198754;
      border-color: #198754;
    }
    .btn-primary:hover {
      background-color: #146c43;
      border-color: #146c43;
    }
     /* Notification dropdown styles */
        .notification-dropdown {
            min-width: 320px;
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s ease;
        }
        .notification-item:hover {
            background-color: #f1f1f1;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .notification-footer {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .notification-badge {
            position: absolute;
            top: 0px;
            right: 0px;
            padding: 0.25rem 0.6rem;
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

         <!-- Notification Dropdown (center/right section) -->
    <div class="d-flex align-items-center ms-auto gap-2 d-none d-lg-flex">
      <!-- <div class="btn-group"> -->
        <div class="dropdown">
          <button class="btn btn-outline-light dropdown-toggle  btn-sm px-3"style="min-width: 150px; height: 38px;" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell me-1"></i> Notifications
            <?php if($notificationCount > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                <?php echo $notificationCount; ?>
              </span>
            <?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
            <div class="notification-header">
              <strong>System Activity</strong>
              <?php if($notificationCount > 0): ?>
                <a href="?mark_read=all" class="text-decoration-none small">Mark all as read</a>
              <?php endif; ?>
            </div>

            <div class="p-3">
              <div class="row mb-3">
                <div class="col-6">
                  <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                    <div>
                      <h6 class="mb-0" style="font-size: 0.8rem;">New Requests</h6>
                      <h5 class="mb-0"><?php echo $newRequestsCount; ?></h5>
                    </div>
                    <div class="bg-success text-white p-2 rounded">
                      <i class="fas fa-file-alt"></i>
                    </div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                    <div>
                      <h6 class="mb-0" style="font-size: 0.8rem;">Registrations</h6>
                      <h5 class="mb-0"><?php echo $newRegistrationsCount; ?></h5>
                    </div>
                    <div class="bg-primary text-white p-2 rounded">
                      <i class="fas fa-user-plus"></i>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <h6 class="mb-2" style="font-size: 0.8rem;">Notification Summary</h6>
                <ul class="list-group list-group-sm">
                  <?php foreach($notificationTypes as $type): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                      <small><?php echo ucfirst(str_replace('_', ' ', $type['request_type'])); ?></small>
                      <span class="badge bg-primary rounded-pill"><?php echo $type['count']; ?></span>
                    </li>
                  <?php endforeach; ?>
                  <?php if(empty($notificationTypes)): ?>
                    <li class="list-group-item text-muted py-1"><small>No notifications</small></li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>

            <div class="notification-header">
              <strong>Recent Activity</strong>
            </div>

            <div class="p-0">
              <ul class="list-group list-group-flush">
                <?php foreach($adminNotifications as $notification): ?>
                  <li class="list-group-item py-2">
                    <div class="d-flex justify-content-between">
                      <?php if($notification['request_type'] == 'registration'): ?>
                        <span><i class="fas fa-user-plus text-primary me-2"></i> <?php echo $notification['message']; ?></span>
                      <?php elseif($notification['request_type'] == 'student_request'): ?>
                        <span><i class="fas fa-file-alt text-success me-2"></i> <?php echo $notification['message']; ?></span>
                      <?php elseif($notification['request_type'] == 'alumni_request'): ?>
                        <span><i class="fas fa-user-graduate text-info me-2"></i> <?php echo $notification['message']; ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="mt-1">
                      <small class="text-muted"><?php echo date('M d, g:i A', strtotime($notification['created_at'])); ?></small>
                    </div>
                  </li>
                <?php endforeach; ?>
                <?php if(empty($adminNotifications)): ?>
                  <li class="list-group-item text-center py-3 text-muted">No recent activity</li>
                <?php endif; ?>
              </ul>
            </div>

            <div class="notification-footer">
              <a href="admin_notifications.php" class="text-decoration-none">View all notifications</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile dropdown -->
    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle btn-sm px-3" style="min-width: 180px; height: 38px;" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <!-- <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li> -->
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
     <!-- Profile dropdown for smaller screens (visible when navbar is collapsed) -->
     <div class="dropdown d-block d-lg-none">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
            <!-- <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li> -->
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
          <h5>Admin Panel</h5>
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
                        <a class="nav-link" href="pending.php">
                            <i class="fas fa-clock me-2"></i>
                            Pending Requests
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
                      <li class="nav-item">
                        <a class="nav-link  active" href="admin_contact_messages.php">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="create_announcement.php">
                            <i class="fas fa-bullhorn me-2"></i>
                            Create Announcement
                        </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link" href="manage_announcements.php">
                            <i class="fas fa-tools me-2"></i>
                            Announcement List
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="admin_notifications.php">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <?php
                            // Get notification count
                            $notifCountQuery = $conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
                            $notifCount = $notifCountQuery->fetch_assoc()['count'];
                            if($notifCount > 0): ?>
                                <span class="badge bg-danger rounded-pill position-absolute top-50 end-0 translate-middle-y me-3"><?php echo $notifCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>
                    </li>           -->
                </ul>
      </div>
    </div>

    <!-- Main content -->
    <main class="col-12 px-md-4 py-4" id="mainContent">
      <h2>Contact Messages</h2>
      <?php if (!$messages): ?>
        <div class="alert alert-info">No messages found.</div>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <div class="message-card">
  <div class="message-info">
    <p class="message-name mb-1"><?= htmlspecialchars($msg['name']) ?></p>
    <small class="message-date"><?= date("F j, Y g:i A", strtotime($msg['created_at'])) ?></small>
  </div>
  <div>
    <button 
      class="btn btn-primary btn-sm me-2" 
      onclick="viewMessage(<?= $msg['id'] ?>)"
      data-bs-toggle="modal" 
      data-bs-target="#messageModal"
    >
      View
    </button>
   <button 
      class="btn btn-danger btn-sm" 
      onclick="openDeleteModal(<?= $msg['id'] ?>)"
      data-bs-toggle="modal" 
      data-bs-target="#deleteConfirmModal"
    >
      Delete
    </button>

  </div>
</div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination">
              <?php for ($i=1; $i<=$total_pages; $i++): ?>
                <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="messageModalLabel"><i class="fas fa-envelope-open-text me-2"></i>Message Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>From:</strong> <span id="modalName"></span> (<span id="modalRole"></span>)</p>
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p id="modalPhoneContainer"><strong>Phone:</strong> <span id="modalPhone"></span></p>
        <p><strong>Received:</strong> <span id="modalDate"></span></p>
        <h5 id="modalSubject" class="text-success"></h5>
        <hr/>
        <p id="modalMessage"></p>
        <hr/>
        <div id="replySection" style="display:none;">
          <label for="replyText" class="form-label"><strong>Reply Message</strong></label>
          <textarea id="replyText" class="form-control" rows="4" placeholder="Type your reply here..."></textarea>
          <div class="mt-3 text-end">
            <button class="btn btn-success" onclick="sendReply()">Send Reply</button>
          </div>
        </div>
        <div id="replyStatus" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
   <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteConfirmLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this message?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  async function viewMessage(id) {
    // Clear previous reply status & textarea
    document.getElementById('replyStatus').textContent = '';
    document.getElementById('replyText').value = '';
    document.getElementById('replySection').style.display = 'none';

    // Fetch message details via AJAX
    try {
      const response = await fetch('fetch_contact_message.php?id=' + id);
      if (!response.ok) throw new Error('Network response was not ok');
      const data = await response.json();

      if (data.error) {
        alert(data.error);
        return;
      }

   
      // Populate modal fields
    document.getElementById('modalSubject').textContent = data.subject;
    document.getElementById('modalName').textContent = data.name;
    document.getElementById('modalRole').textContent = data.role;
    document.getElementById('modalEmail').textContent = data.email;
    document.getElementById('modalDate').textContent = new Date(data.created_at).toLocaleString();
    document.getElementById('modalMessage').textContent = data.message;

    // Conditionally show/hide phone
    if (data.phone && data.phone.trim() !== '') {
      document.getElementById('modalPhone').textContent = data.phone;
      document.getElementById('modalPhoneContainer').style.display = 'block';
    } else {
      document.getElementById('modalPhoneContainer').style.display = 'none';
    }

    // Show reply box only if role is student or alumni
    if (data.role === 'student' || data.role === 'alumni') {
      document.getElementById('replySection').style.display = 'block';
      document.getElementById('replySection').dataset.messageId = id;
    } else {
      document.getElementById('replySection').style.display = 'none';
    }

    } catch (err) {
      alert('Failed to load message details.');
      console.error(err);
    }
  }

  async function sendReply() {
    const replyText = document.getElementById('replyText').value.trim();
    const messageId = document.getElementById('replySection').dataset.messageId;

    if (!replyText) {
      alert('Reply message cannot be empty.');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('message_id', messageId);
      formData.append('reply_message', replyText);

    const response = await fetch('send_contact_reply.php', {
  method: 'POST',
  headers: {
    'X-Requested-With': 'XMLHttpRequest'
  },
  body: formData
});



      const result = await response.json();

      if (result.success) {
        document.getElementById('replyStatus').textContent = 'Reply sent successfully!';
        document.getElementById('replyStatus').className = 'text-success';
        document.getElementById('replyText').value = '';
      } else {
        const errMsg = result.error || 'Failed to send reply.';
  document.getElementById('replyStatus').textContent = errMsg;
  document.getElementById('replyStatus').className = 'text-danger';
      }
    } catch (err) {
      document.getElementById('replyStatus').textContent = 'Error sending reply.';
      document.getElementById('replyStatus').className = 'text-danger';
      console.error(err);
    }
  }
</script>
<script>
  let deleteMessageId = null;

  function openDeleteModal(id) {
    deleteMessageId = id;
  }

  document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!deleteMessageId) return;

    try {
      const response = await fetch('delete_contact_message.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: `id=${encodeURIComponent(deleteMessageId)}`
      });

      const result = await response.json();
      if (result.success) {
        // Optionally close the modal
        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        deleteModal.hide();

        // Refresh the page or remove the deleted card dynamically
        location.reload();
      } else {
        alert(result.error || 'Failed to delete message.');
      }
    } catch (err) {
      alert('An error occurred while deleting the message.');
      console.error(err);
    }
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
