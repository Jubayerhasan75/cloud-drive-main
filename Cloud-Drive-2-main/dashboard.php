<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
include 'db.php';
$user_id = (int)$_SESSION['user_id'];
$current_folder = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$trash_view = isset($_GET['trash']);
$favorite_view = isset($_GET['favorite']);

function getFileIcon($file_type) {
    if (strpos($file_type, 'image/') === 0) return 'fa-image';
    if (strpos($file_type, 'video/') === 0) return 'fa-video';
    if (strpos($file_type, 'audio/') === 0) return 'fa-headphones';
    if (strpos($file_type, 'text/') === 0) return 'fa-file-alt';
    if ($file_type == 'application/pdf') return 'fa-file-pdf';
    if ($file_type == 'application/msword' || $file_type == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') return 'fa-file-word';
    if ($file_type == 'application/vnd.ms-excel' || $file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') return 'fa-file-excel';
    if ($file_type == 'application/vnd.ms-powerpoint' || $file_type == 'application/vnd.openxmlformats-officedocument.presentationml.presentation') return 'fa-file-powerpoint';
    if ($file_type == 'application/zip' || $file_type == 'application/x-rar-compressed') return 'fa-file-archive';
    return 'fa-file';
}

$fav_files = [];
$fav_result = $conn->query("SELECT file_id FROM favorites WHERE user_id=$user_id");
while ($row = $fav_result->fetch_assoc()) {
    $fav_files[$row['file_id']] = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - File Manager Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --main-bg: #f8f9fa;
            --sidebar-bg: #ffffff;
            --border-color: #e9ecef;
            --text-color: #495057;
            --accent-blue: #007bff;
            --dark-gray: #6c757d;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-color);
            margin: 0;
        }

        .main-container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: width 0.3s ease;
        }

        .logo-section {
            display: flex;
            align-items: center;
            margin-bottom: 32px;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .logo-icon {
            color: var(--accent-blue);
            margin-right: 8px;
        }

        .nav-menu {
            width: 100%;
            padding: 0 16px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            color: var(--dark-gray);
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .nav-item:hover, .nav-item.active {
            background-color: #f1f3f5;
            color: var(--accent-blue);
            text-decoration: none;
        }
        .nav-icon {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex-grow: 1;
            padding: 0 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .search-container {
            flex-grow: 1;
            margin-right: 24px;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #ffffff;
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
        }
        .action-buttons .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .dashboard-body {
            padding: 24px 0;
        }

        .dropzone-area {
            height: 200px;
            background-color: #f1f3f5;
            border: 2px dashed #ced4da;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-bottom: 40px;
            cursor: pointer;
        }
        .dropzone-icon {
            font-size: 3rem;
            color: #adb5bd;
        }
        .dropzone-text {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-gray);
            margin: 8px 0 4px;
        }
        .dropzone-subtext {
            font-size: 0.9rem;
            color: #868e96;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 20px;
        }
        .quick-access-cards {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 16px;
        }
        .quick-access-card {
            flex-shrink: 0;
            width: 250px;
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: var(--box-shadow);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .quick-access-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }
        .quick-access-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .card-icon {
            font-size: 1.5rem;
            margin-right: 16px;
        }
        .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 4px;
        }
        .card-content p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--dark-gray);
        }
        .card-content small {
            font-size: 0.8rem;
            color: #adb5bd;
        }
        .card-arrow {
            margin-left: auto;
            font-size: 1rem;
            color: var(--dark-gray);
        }
        .files-folders-section .card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .item-name {
            font-size: 1rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .preview-image {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
            border-radius: 6px;
        }
        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid var(--border-color);
            padding: 8px 12px;
        }
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        .notification.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <aside class="sidebar">
            <div class="logo-section">
                <i class="fas fa-cubes logo-icon"></i>
                <span class="logo-text">File Manager Pro</span>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item active"><i class="fas fa-home nav-icon"></i> Home</a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-navbar">
                <div class="search-container">
                    <form action="dashboard.php" method="GET">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" placeholder="Search files and folders..." value="<?php echo htmlspecialchars($q); ?>">
                    </form>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#folderModal">
                        <i class="fas fa-plus"></i> New Folder
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </header>

            <div class="dashboard-body">
                <div class="dropzone-area" id="dropzone">
                    <div class="dropzone-content">
                        <i class="fas fa-cloud-upload-alt dropzone-icon"></i>
                        <p class="dropzone-text">Drop files here to upload</p>
                        <p class="dropzone-subtext">or click to browse your computer</p>
                    </div>
                </div>

                <section class="quick-access-section">
                    <h2>Quick Access</h2>
                    <div class="quick-access-cards">
                        <a href="dashboard.php?favorite=1" class="quick-access-card">
                            <div class="card-icon"><i class="fas fa-star text-warning"></i></div>
                            <div class="card-content">
                                <h3>Favorites</h3>
                                <p id="favorite-count"><?php
                                    $fav_count = $conn->query("SELECT COUNT(*) FROM favorites WHERE user_id=$user_id")->fetch_row()[0];
                                    echo $fav_count . " items";
                                ?></p>
                                <small>Updated today</small>
                            </div>
                            <div class="card-arrow"><i class="fas fa-chevron-right"></i></div>
                        </a>
                        <a href="dashboard.php?trash=1" class="quick-access-card">
                            <div class="card-icon"><i class="fas fa-trash-alt text-danger"></i></div>
                            <div class="card-content">
                                <h3>Trash</h3>
                                <p><?php
                                    $trash_count = $conn->query("SELECT COUNT(*) FROM files WHERE user_id=$user_id AND deleted_at IS NOT NULL")->fetch_row()[0];
                                    echo $trash_count . " items";
                                ?></p>
                                <small>Yesterday</small>
                            </div>
                            <div class="card-arrow"><i class="fas fa-chevron-right"></i></div>
                        </a>
                    </div>
                </section>

                <section class="files-folders-section">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">My Drive</a></li>
                            <?php
                            if ($current_folder) {
                                $folder_result = $conn->query("SELECT folder_name FROM folders WHERE id=$current_folder");
                                if ($folder_result && $folder_result->num_rows > 0) {
                                    $folder_row = $folder_result->fetch_assoc();
                                    echo "<li class='breadcrumb-item active'>" . htmlspecialchars($folder_row['folder_name']) . "</li>";
                                }
                            }
                            ?>
                        </ol>
                    </nav>

                    <h2 class="mb-3">
                        <?php
                            if ($q !== '') {
                                echo "Search results for \"" . htmlspecialchars($q) . "\"";
                            } elseif ($trash_view) {
                                echo "Trash";
                            } elseif ($favorite_view) {
                                echo "Your Favorite Files";
                            } else {
                                echo "Folders";
                            }
                        ?>
                    </h2>

                    <?php if ($trash_view || $favorite_view): ?>
                        <div class="row g-3">
                            <?php
                                $files_query = "";
                                if ($trash_view) {
                                    $files_query = "SELECT * FROM files WHERE user_id=$user_id AND deleted_at IS NOT NULL";
                                } elseif ($favorite_view) {
                                    $files_query = "SELECT f.* FROM files f JOIN favorites fav ON f.id=fav.file_id WHERE fav.user_id=$user_id AND f.deleted_at IS NULL";
                                }
                                $files = $conn->query($files_query);
                                if ($files && $files->num_rows > 0) {
                                    while ($file = $files->fetch_assoc()) {
                                        $is_image = strpos($file['file_type'], 'image/') === 0;
                                        $file_icon = getFileIcon($file['file_type']);
                                        $is_fav = isset($fav_files[(int)$file['id']]);
                                        $cardId = 'file-card-' . (int)$file['id'];
                                        echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3' id='$cardId'>";
                                        echo "<div class='card h-100'>";
                                        echo "<div class='card-body text-center d-flex flex-column align-items-center justify-content-center'>";
                                        if ($is_image) {
                                            echo "<img src='" . htmlspecialchars($file['filepath']) . "' alt='" . htmlspecialchars($file['filename']) . "' class='preview-image mb-2'>";
                                        } else {
                                            echo "<i class='fas " . $file_icon . " fa-3x text-secondary mb-2'></i>";
                                        }
                                        echo "<div class='item-name fw-semibold'>" . htmlspecialchars($file['filename']) . "</div>";
                                        // Added favorite button for trash and favorite views
                                        $btnClass = $is_fav ? 'btn-warning' : 'btn-outline-warning';
                                        $iconStyle = $is_fav ? 'fas' : 'far';
                                        echo "<div class='mt-2'><button class='btn btn-sm {$btnClass} favorite-btn' data-file='".(int)$file['id']."' onclick='toggleFavorite(".(int)$file['id'].", this)'><i class='{$iconStyle} fa-star'></i></button></div>";
                                        echo "</div>";
                                        echo "<div class='card-footer d-flex justify-content-around bg-light'>";
                                        if ($trash_view) {
                                            $folder_id_val = isset($file['folder_id']) && $file['folder_id'] !== null ? (int)$file['folder_id'] : 0;
                                            echo "<form method='POST' action='restore.php' style='display:inline;margin:0;padding:0;'>";
                                            echo "<input type='hidden' name='id' value='" . (int)$file['id'] . "'>";
                                            echo "<input type='hidden' name='folder_id' value='" . $folder_id_val . "'>";
                                            echo "<button class='btn btn-sm btn-outline-success' type='submit' title='Restore'><i class='fas fa-undo'></i></button>";
                                            echo "</form>";
                                            echo "<a href='delete.php?id=" . (int)$file['id'] . "&force=1' class='btn btn-sm btn-outline-danger' title='Delete permanently' onclick='return confirm(\"Are you sure you want to permanently delete this file?\")'><i class='fas fa-trash'></i></a>";
                                        } else {
                                            echo "<a href='" . htmlspecialchars($file['filepath']) . "' download class='btn btn-sm btn-outline-primary' title='Download'><i class='fas fa-download'></i></a>";
                                            echo "<button class='btn btn-sm btn-outline-secondary' title='Rename' onclick='openRenameFileModal(" . (int)$file['id'] . ", \"" . addslashes($file['filename']) . "\")'><i class='fas fa-edit'></i></button>";
                                            echo "<a href='delete.php?id=" . (int)$file['id'] . "' class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></a>";
                                            echo "<button class='btn btn-sm btn-outline-success' title='Share' onclick='openShareModal(" . (int)$file['id'] . ")'><i class='fas fa-share-alt'></i></button>";
                                        }
                                        echo "</div></div></div>";
                                    }
                                } else {
                                    echo "<div class='col-12'><p class='text-center'>No items found.</p></div>";
                                }
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="row g-3 mb-4">
                            <?php
                            $folder_query = $current_folder
                                ? "SELECT * FROM folders WHERE user_id=$user_id AND parent_id=$current_folder"
                                : "SELECT * FROM folders WHERE user_id=$user_id AND parent_id IS NULL";
                            $folders = $conn->query($folder_query);
                            if ($folders && $folders->num_rows > 0) {
                                while ($folder = $folders->fetch_assoc()) {
                                    echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
                                    echo "<div class='card folder-card h-100'>";
                                    echo "<div class='card-body text-center d-flex flex-column align-items-center justify-content-center'>";
                                    echo "<i class='fas fa-folder fa-3x text-warning mb-2'></i>";
                                    echo "<div class='item-name fw-semibold'>" . htmlspecialchars($folder['folder_name']) . "</div>";
                                    echo "</div>";
                                    echo "<div class='card-footer d-flex justify-content-around bg-light'>";
                                    echo "<a href='dashboard.php?folder_id=".(int)$folder['id']."' class='btn btn-sm btn-outline-primary' title='Open'><i class='fas fa-folder-open'></i></a>";
                                    echo "<button class='btn btn-sm btn-outline-secondary' title='Rename' onclick='openRenameFolderModal(".(int)$folder['id'].", \"".addslashes($folder['folder_name'])."\")'><i class='fas fa-edit'></i></button>";
                                    echo "<a href='delete_folder.php?id=".(int)$folder['id']."' class='btn btn-sm btn-outline-danger' title='Delete' onclick='return confirm(\"Are you sure you want to delete this folder?\")'><i class='fas fa-trash'></i></a>";
                                    echo "</div></div></div>";
                                }
                            } else {
                                echo "<div class='col-12'><p class='text-center'>No folders found</p></div>";
                            }
                            ?>
                        </div>

                        <h2 class="mt-4 mb-3">Files</h2>
                        <div class="row g-3">
                            <?php
                                $files = null;
                                if ($q !== '') {
                                    $like = "%$q%";
                                    if ($current_folder) {
                                        $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND folder_id=? AND filename LIKE ? AND deleted_at IS NULL ORDER BY uploaded_at DESC");
                                        $stmt->bind_param("iis", $user_id, $current_folder, $like);
                                        $stmt->execute();
                                        $files = $stmt->get_result();
                                    } else {
                                        $stmt = $conn->prepare("SELECT * FROM files WHERE user_id=? AND folder_id IS NULL AND filename LIKE ? AND deleted_at IS NULL ORDER BY uploaded_at DESC");
                                        $stmt->bind_param("is", $user_id, $like);
                                        $stmt->execute();
                                        $files = $stmt->get_result();
                                    }
                                } else {
                                    $file_query = $current_folder
                                        ? "SELECT * FROM files WHERE user_id=$user_id AND folder_id=$current_folder AND deleted_at IS NULL ORDER BY uploaded_at DESC"
                                        : "SELECT * FROM files WHERE user_id=$user_id AND folder_id IS NULL AND deleted_at IS NULL ORDER BY uploaded_at DESC";
                                    $files = $conn->query($file_query);
                                }

                                if ($files && $files->num_rows > 0) {
                                    while ($file = $files->fetch_assoc()) {
                                        $is_image = strpos($file['file_type'], 'image/') === 0;
                                        $file_icon = getFileIcon($file['file_type']);
                                        $is_fav = isset($fav_files[(int)$file['id']]);
                                        $cardId = 'file-card-' . (int)$file['id'];
                                        echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3' id='$cardId'>";
                                        echo "<div class='card h-100'>";
                                        echo "<div class='card-body text-center d-flex flex-column align-items-center justify-content-center'>";
                                        if ($is_image) {
                                            echo "<img src='".htmlspecialchars($file['filepath'])."' alt='".htmlspecialchars($file['filename'])."' class='preview-image mb-2'>";
                                        } else {
                                            echo "<i class='fas $file_icon fa-3x text-secondary mb-2'></i>";
                                        }
                                        echo "<div class='item-name fw-semibold'>". htmlspecialchars($file['filename']) . "</div>";
                                        $btnClass = $is_fav ? 'btn-warning' : 'btn-outline-warning';
                                        $iconStyle = $is_fav ? 'fas' : 'far';
                                        echo "<div class='mt-2'><button class='btn btn-sm {$btnClass} favorite-btn' data-file='".(int)$file['id']."' onclick='toggleFavorite(".(int)$file['id'].", this)'><i class='{$iconStyle} fa-star'></i></button></div>";
                                        echo "</div>";
                                        echo "<div class='card-footer d-flex justify-content-around bg-light'>";
                                        echo "<a href='".htmlspecialchars($file['filepath'])."' download class='btn btn-sm btn-outline-primary' title='Download'><i class='fas fa-download'></i></a>";
                                        echo "<button class='btn btn-sm btn-outline-secondary' title='Rename' onclick='openRenameFileModal(".(int)$file['id'].", \"".addslashes($file['filename'])."\")'><i class='fas fa-edit'></i></button>";
                                        echo "<a href='delete.php?id=".(int)$file['id']."' class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></a>";
                                        echo "<button class='btn btn-sm btn-outline-success' title='Share' onclick='openShareModal(".(int)$file['id'].")'><i class='fas fa-share-alt'></i></button>";
                                        echo "</div></div></div>";
                                    }
                                } else {
                                    echo "<div class='col-12'><p class='text-center'>No files found</p></div>";
                                }
                            ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            <div id="notification-toast" class="notification"></div>
        </main>
    </div>

    <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="create_folder.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="folderModalLabel">Create New Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folderName" name="folder_name" required>
                            <?php if ($current_folder): ?>
                                <input type="hidden" name="parent_id" value="<?php echo (int)$current_folder; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fileInput" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="fileInput" name="file" required>
                        </div>
                        <div class="mb-3">
                            <label for="filenameInput" class="form-label">File Name</label>
                            <input type="text" class="form-control" id="filenameInput" name="filename" required>
                        </div>
                        <?php if ($current_folder): ?>
                            <input type="hidden" name="folder_id" value="<?php echo (int)$current_folder; ?>">
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="rename.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameModalLabel">Rename</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="type" id="renameType">
                        <input type="hidden" name="id" id="renameId">
                        <div class="mb-3">
                            <label for="newName" class="form-label">New Name</label>
                            <input type="text" class="form-control" id="newName" name="new_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="shareLink" class="form-label">Shareable Link</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareLink" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyShareLink">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Custom notification function instead of alert
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification-toast');
            notification.textContent = message;
            notification.style.backgroundColor = isError ? '#dc3545' : '#28a745';
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function openRenameFolderModal(id, name) {
            document.getElementById('renameType').value = 'folder';
            document.getElementById('renameId').value = id;
            document.getElementById('newName').value = name;
            var renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
            renameModal.show();
        }

        function openRenameFileModal(id, name) {
            document.getElementById('renameType').value = 'file';
            document.getElementById('renameId').value = id;
            document.getElementById('newName').value = name;
            var renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
            renameModal.show();
        }

        function openShareModal(fileId) {
            var shareLinkInput = document.getElementById('shareLink');
            shareLinkInput.value = '<?php echo (isset($_SERVER["HTTPS"]) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>' + '/share.php?id=' + fileId;
            var shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
            shareModal.show();
        }

        document.getElementById('copyShareLink').addEventListener('click', function() {
            var shareLinkInput = document.getElementById('shareLink');
            shareLinkInput.select();
            try {
                document.execCommand('copy');
                showNotification('Link copied to clipboard!');
            } catch (err) {
                showNotification('Failed to copy link.', true);
            }
        });

        async function toggleFavorite(fileId, button) {
            const response = await fetch('toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'file_id=' + fileId
            });
            const result = await response.json();
            if (result.success) {
                const icon = button.querySelector('i');
                const favCountEl = document.getElementById('favorite-count');
                let currentCount = parseInt(favCountEl.textContent.match(/\d+/)[0], 10);

                if (result.is_favorite) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.classList.remove('btn-outline-warning');
                    button.classList.add('btn-warning');
                    currentCount++;
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    button.classList.remove('btn-warning');
                    button.classList.add('btn-outline-warning');
                    currentCount--;
                    // Remove card from DOM if on favorite view
                    if (window.location.search.includes('favorite=1')) {
                        const cardToRemove = document.getElementById('file-card-' + fileId);
                        if (cardToRemove) {
                            cardToRemove.remove();
                        }
                    }
                }
                favCountEl.textContent = `${currentCount} items`;
            } else {
                showNotification('Failed to update favorite status.', true);
            }
        }

        // Dropzone functionality
        const dropzone = document.getElementById('dropzone');
        const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));

        dropzone.addEventListener('click', () => {
            uploadModal.show();
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('drag-over');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            
            const file = e.dataTransfer.files[0];
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('fileInput').files = dataTransfer.files;
                document.getElementById('filenameInput').value = file.name;
                uploadModal.show();
            }
        });
    </script>
</body>
</html>
