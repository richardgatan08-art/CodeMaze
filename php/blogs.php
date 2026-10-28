<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list':
        $rows = $pdo->query('SELECT * FROM blog_entries ORDER BY week DESC')->fetchAll();
        foreach ($rows as &$row) {
            $row['date'] = date('M d, Y g:i A', strtotime($row['created_at']));
        }
        unset($row);
        echo json_encode(['success' => true, 'blogs' => $rows]);
        break;

    // ── ADD / UPDATE ──────────────────────────────────────────────────────────
    case 'add':
        requireAdmin();

        $week = (int)($_POST['week'] ?? 0);
        $text = trim($_POST['text'] ?? '');

        if ($week < 1) {
            echo json_encode(['success' => false, 'error' => 'Please enter a valid week number.']);
            exit;
        }
        if (!$text) {
            echo json_encode(['success' => false, 'error' => 'Blog content cannot be empty.']);
            exit;
        }

        // Check for duplicate week
        $check = $pdo->prepare('SELECT id FROM blog_entries WHERE week = ?');
        $check->execute([$week]);
        $existing = $check->fetch();

        if ($existing) {
            // Update existing
            $upd = $pdo->prepare(
                'UPDATE blog_entries SET text = ?, created_at = NOW() WHERE week = ?'
            );
            $upd->execute([$text, $week]);
            $entryId = $existing['id'];
            $updated = true;
        } else {
            // Insert new
            $ins = $pdo->prepare(
                'INSERT INTO blog_entries (week, text) VALUES (?, ?)'
            );
            $ins->execute([$week, $text]);
            $entryId = $pdo->lastInsertId();
            $updated = false;
        }

        // Fetch the saved row
        $row = $pdo->prepare('SELECT * FROM blog_entries WHERE id = ?');
        $row->execute([$entryId]);
        $entry = $row->fetch();
        $entry['date'] = date('M d, Y g:i A', strtotime($entry['created_at']));

        echo json_encode([
            'success' => true,
            'updated' => $updated,
            'entry'   => $entry,
        ]);
        break;

// ── EDIT (by id) ──────────────────────────────────────────────────────────
    case 'edit':
        requireAdmin();

        $id   = (int)($_POST['id'] ?? 0);
        $week = (int)($_POST['week'] ?? 0);
        $text = trim($_POST['text'] ?? '');

        if ($id < 1) {
            echo json_encode(['success' => false, 'error' => 'Invalid entry ID.']);
            exit;
        }
        if ($week < 1) {
            echo json_encode(['success' => false, 'error' => 'Please enter a valid week number.']);
            exit;
        }
        if (!$text) {
            echo json_encode(['success' => false, 'error' => 'Blog content cannot be empty.']);
            exit;
        }

        // Ensure the entry exists
        $check = $pdo->prepare('SELECT id FROM blog_entries WHERE id = ?');
        $check->execute([$id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Entry not found.']);
            exit;
        }

        // Check for duplicate week (excluding this entry)
        $dup = $pdo->prepare('SELECT id FROM blog_entries WHERE week = ? AND id != ?');
        $dup->execute([$week, $id]);
        if ($dup->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Another entry already uses week ' . $week . '.']);
            exit;
        }

        $upd = $pdo->prepare('UPDATE blog_entries SET week = ?, text = ?, created_at = NOW() WHERE id = ?');
        $upd->execute([$week, $text, $id]);

        $row = $pdo->prepare('SELECT * FROM blog_entries WHERE id = ?');
        $row->execute([$id]);
        $entry = $row->fetch();
        $entry['date'] = date('M d, Y g:i A', strtotime($entry['created_at']));

        echo json_encode(['success' => true, 'entry' => $entry, 'message' => 'Blog entry updated!']);
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            echo json_encode(['success' => false, 'error' => 'Invalid entry ID.']);
            exit;
        }

        $del = $pdo->prepare('DELETE FROM blog_entries WHERE id = ?');
        $del->execute([$id]);

        if ($del->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Entry not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Blog entry deleted.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
