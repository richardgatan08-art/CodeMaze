<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// Uploads directory (relative to project root)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '../uploads/');   // relative URL for the browser

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list':
        $rows = $pdo->query('SELECT * FROM slides ORDER BY id DESC')->fetchAll();
        // Build a web-accessible URL for each image
        foreach ($rows as &$row) {
            $row['image_url'] = '../uploads/' . rawurlencode($row['image']);
            $row['date'] = date('M d, Y g:i A', strtotime($row['created_at']));
        }
        unset($row);
        echo json_encode(['success' => true, 'slides' => $rows]);
        break;

    // ── ADD ───────────────────────────────────────────────────────────────────
    case 'add':
        requireAdmin();

        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if (!$desc) {
            echo json_encode(['success' => false, 'error' => 'Description is required.']);
            exit;
        }

        // Validate uploaded file
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Please select a valid image file.']);
            exit;
        }

        $file     = $_FILES['image'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Only JPEG, PNG, GIF and WebP images are allowed.']);
            exit;
        }

        // Give the file a unique name
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('slide_', true) . '.' . strtolower($ext);
        $dest     = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save the uploaded image.']);
            exit;
        }

        // Insert into DB
        $stmt = $pdo->prepare(
            'INSERT INTO slides (title, description, image) VALUES (?, ?, ?)'
        );
        $stmt->execute([$title ?: null, $desc, $filename]);
        $newId = $pdo->lastInsertId();

        // Fetch the new row to return it
        $row = $pdo->prepare('SELECT * FROM slides WHERE id = ?');
        $row->execute([$newId]);
        $slide = $row->fetch();
        $slide['image_url'] = '../uploads/' . rawurlencode($slide['image']);
        $slide['date']      = date('M d, Y g:i A', strtotime($slide['created_at']));

        echo json_encode(['success' => true, 'slide' => $slide]);
        break;

    // ── EDIT ──────────────────────────────────────────────────────────────────
    case 'edit':
        requireAdmin();

        $id    = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if ($id < 1) {
            echo json_encode(['success' => false, 'error' => 'Invalid slide ID.']);
            exit;
        }
        if (!$desc) {
            echo json_encode(['success' => false, 'error' => 'Description is required.']);
            exit;
        }

        // Fetch existing slide
        $stmt = $pdo->prepare('SELECT * FROM slides WHERE id = ?');
        $stmt->execute([$id]);
        $existingSlide = $stmt->fetch();

        if (!$existingSlide) {
            echo json_encode(['success' => false, 'error' => 'Slide not found.']);
            exit;
        }

        $filename = $existingSlide['image'];

        // If a new image file is uploaded
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['image'];
            $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowed)) {
                echo json_encode(['success' => false, 'error' => 'Only JPEG, PNG, GIF and WebP images are allowed.']);
                exit;
            }

            $ext         = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('slide_', true) . '.' . strtolower($ext);
            $dest        = UPLOAD_DIR . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Delete old file
                $oldPath = UPLOAD_DIR . $existingSlide['image'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                $filename = $newFilename;
            }
        }

        $upd = $pdo->prepare('UPDATE slides SET title = ?, description = ?, image = ? WHERE id = ?');
        $upd->execute([$title ?: null, $desc, $filename, $id]);

        $row = $pdo->prepare('SELECT * FROM slides WHERE id = ?');
        $row->execute([$id]);
        $slide = $row->fetch();
        $slide['image_url'] = '../uploads/' . rawurlencode($slide['image']);
        $slide['date']      = date('M d, Y g:i A', strtotime($slide['created_at']));

        echo json_encode(['success' => true, 'slide' => $slide, 'message' => 'Slide updated successfully!']);
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            echo json_encode(['success' => false, 'error' => 'Invalid slide ID.']);
            exit;
        }

        // Fetch filename before deleting
        $stmt = $pdo->prepare('SELECT image FROM slides WHERE id = ?');
        $stmt->execute([$id]);
        $slide = $stmt->fetch();

        if (!$slide) {
            echo json_encode(['success' => false, 'error' => 'Slide not found.']);
            exit;
        }

        // Remove the physical file
        $filePath = UPLOAD_DIR . $slide['image'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete from DB
        $del = $pdo->prepare('DELETE FROM slides WHERE id = ?');
        $del->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Slide deleted.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
