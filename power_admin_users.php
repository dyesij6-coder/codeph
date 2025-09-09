<?php
include 'config.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') { 
    header('Location: login.php'); 
    exit(); 
}

// Pagination logic
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM users";
$totalQuery = "SELECT COUNT(*) as total FROM users";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRows = (int)$totalRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$query = "SELECT * FROM users LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Get user statistics
$statsQuery = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'Student' THEN 1 ELSE 0 END) as students,
    SUM(CASE WHEN role = 'Admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN role = 'Power Admin' THEN 1 ELSE 0 END) as power_admins
    FROM users";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Admin - Users</title>
    <link rel="stylesheet" href="assets/admin_theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        .stat-card i {
            font-size: 30px;
            margin-bottom: 10px;
            color: var(--accent-color);
        }
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
        }
        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .pagination a:hover {
            background: var(--bg-color);
            border-color: var(--accent-color);
        }
        .pagination .current {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <?php include 'power_admin_header.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-users"></i> User Management
                </h1>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-number"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-graduate"></i>
                    <div class="stat-number"><?= $stats['students'] ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-shield"></i>
                    <div class="stat-number"><?= $stats['admins'] ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-crown"></i>
                    <div class="stat-number"><?= $stats['power_admins'] ?></div>
                    <div class="stat-label">Power Admins</div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $row['role'] === 'Power Admin' ? 'danger' : ($row['role'] === 'Admin' ? 'warning' : 'info') ?>">
                                            <?= htmlspecialchars($row['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($row['status'] === 'active'): ?>
                                                <button class="btn btn-small btn-danger" onclick="toggleUserStatus(<?= $row['id'] ?>, 'inactive')">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-small btn-success" onclick="toggleUserStatus(<?= $row['id'] ?>, 'active')">
                                                    <i class="fas fa-check"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-small btn-warning" onclick="resetPassword(<?= $row['id'] ?>)">
                                                <i class="fas fa-key"></i> Reset
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'current' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUserStatus(userId, newStatus) {
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this user?`)) {
                // You can implement AJAX call here
                console.log(`Toggling user ${userId} to ${newStatus}`);
                // For now, just reload the page
                location.reload();
            }
        }
        
        function resetPassword(userId) {
            if (confirm('Are you sure you want to reset this user\'s password?')) {
                // You can implement AJAX call here
                console.log(`Resetting password for user ${userId}`);
                // For now, just reload the page
                location.reload();
            }
        }
    </script>
</body>
</html>