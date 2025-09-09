<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') { 
    header('Location: login.php'); 
    exit(); 
}

include 'config.php';

$message = "";
$uploadDir = __DIR__ . "/uploads/announcements";
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        $title = trim($_POST['title'] ?? '');
        $title = ($title !== '') ? $title : 'No Title';
        $webDir = 'uploads/announcements/';
        $orig = basename($_FILES['image']['name'] ?? '');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if ($orig && in_array($ext, $allowed, true)) {
            $safeName = sprintf('%d_%s.%s', time(), bin2hex(random_bytes(4)), $ext);
            $dest = $uploadDir . '/' . $safeName;
            if (@move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $stmt = $conn->prepare("INSERT INTO announcements (title, image) VALUES (?, ?)");
                $stmt->bind_param('ss', $title, $safeName);
                if ($stmt->execute()) { 
                    $message = '<div class="alert alert-success">Announcement added successfully!</div>'; 
                } else { 
                    $message = '<div class="alert alert-error">Error saving to database!</div>'; 
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-error">Error uploading file!</div>';
            }
        } else {
            $message = '<div class="alert alert-error">Invalid file type! Please upload JPG, JPEG, PNG, or GIF.</div>';
        }
    }

    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        
        if ($row) {
            @unlink($uploadDir . "/" . $row['image']);
            $del = $conn->prepare("DELETE FROM announcements WHERE id = ?");
            $del->bind_param('i', $id);
            if ($del->execute()) {
                $message = '<div class="alert alert-success">Announcement deleted successfully!</div>';
            } else {
                $message = '<div class="alert alert-error">Error deleting announcement!</div>';
            }
            $del->close();
        }
    }

    if (isset($_POST['update'])) {
        $id = (int)($_POST['id'] ?? 0);
        $title = !empty($_POST['title']) ? $_POST['title'] : "No Title";
        $fileName = basename($_FILES["image"]["name"] ?? '');
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!empty($fileName)) {
            $allowedTypes = array("jpg", "jpeg", "png", "gif");
            if (in_array($fileType, $allowedTypes)) {
                $safeName = sprintf('%d_%s.%s', time(), bin2hex(random_bytes(4)), $fileType);
                $dest = $uploadDir . '/' . $safeName;
                if (@move_uploaded_file($_FILES["image"]["tmp_name"], $dest)) {
                    $qs = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
                    $qs->bind_param('i', $id);
                    $qs->execute();
                    $res2 = $qs->get_result();
                    $row = $res2 ? $res2->fetch_assoc() : null;
                    $qs->close();
                    if ($row && !empty($row['image'])) { @unlink($uploadDir . "/" . $row['image']); }
                    $upd = $conn->prepare("UPDATE announcements SET title = ?, image = ? WHERE id = ?");
                    $upd->bind_param('ssi', $title, $safeName, $id);
                    if ($upd->execute()) {
                        $message = '<div class="alert alert-success">Announcement updated successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-error">Error updating announcement!</div>';
                    }
                    $upd->close();
                } else {
                    $message = '<div class="alert alert-error">Error uploading file!</div>';
                }
            } else {
                $message = '<div class="alert alert-error">Invalid file type! Please upload JPG, JPEG, PNG, or GIF.</div>';
            }
        } else {
            $upd = $conn->prepare("UPDATE announcements SET title = ? WHERE id = ?");
            $upd->bind_param('si', $title, $id);
            if ($upd->execute()) {
                $message = '<div class="alert alert-success">Announcement updated successfully!</div>';
            } else {
                $message = '<div class="alert alert-error">Error updating announcement!</div>';
            }
            $upd->close();
        }
    }
}

$result = $conn->query("SELECT * FROM announcements ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Admin - Announcements</title>
    <link rel="stylesheet" href="assets/admin_theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .announcement-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .announcement-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .announcement-content {
            padding: 15px;
        }
        .announcement-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        .announcement-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            background: var(--accent-color);
            color: white;
            padding: 10px 20px;
            border-radius: var(--radius);
            transition: background-color 0.3s ease;
        }
        .file-upload:hover {
            background: var(--secondary-color);
        }
        .file-upload input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: var(--card-bg);
            margin: 5% auto;
            padding: 20px;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
        }
        .close {
            color: var(--text-muted);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <?php include 'power_admin_header.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-bullhorn"></i> Announcement Management
                </h1>
            </div>
            
            <?php if ($message): ?>
                <?= $message ?>
            <?php endif; ?>
            
            <!-- Add Announcement Form -->
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="title">Announcement Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="image">Image File</label>
                        <label class="file-upload">
                            <i class="fas fa-upload"></i> Choose Image
                            <input type="file" id="image" name="image" accept="image/*" required>
                        </label>
                        <div class="file-name" id="fileName"></div>
                    </div>
                </div>
                <button type="submit" name="add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Announcement
                </button>
            </form>
            
            <!-- Announcements Grid -->
            <div class="announcement-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="announcement-card">
                            <img src="uploads/announcements/<?= htmlspecialchars($row['image']) ?>" 
                                 alt="<?= htmlspecialchars($row['title']) ?>" 
                                 class="announcement-image">
                            <div class="announcement-content">
                                <div class="announcement-title"><?= htmlspecialchars($row['title']) ?></div>
                                <div class="announcement-actions">
                                    <button class="btn btn-small btn-warning" onclick="editAnnouncement(<?= $row['id'] ?>, '<?= htmlspecialchars($row['title']) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-small btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this announcement?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center" style="grid-column: 1 / -1; padding: 40px;">
                        <i class="fas fa-bullhorn" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;"></i>
                        <h3 style="color: var(--text-muted);">No announcements found</h3>
                        <p style="color: var(--text-muted);">Add your first announcement using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Announcement</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label class="form-label" for="editTitle">Title</label>
                    <input type="text" class="form-control" id="editTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="editImage">New Image (optional)</label>
                    <label class="file-upload">
                        <i class="fas fa-upload"></i> Choose New Image
                        <input type="file" id="editImage" name="image" accept="image/*">
                    </label>
                </div>
                <button type="submit" name="update" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Announcement
                </button>
            </form>
        </div>
    </div>

    <script>
        // File upload display
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file chosen';
            document.getElementById('fileName').textContent = fileName;
        });

        // Edit modal functionality
        const modal = document.getElementById('editModal');
        const closeBtn = document.querySelector('.close');

        function editAnnouncement(id, title) {
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            modal.style.display = 'block';
        }

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>