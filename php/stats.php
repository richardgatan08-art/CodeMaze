<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$slideCount  = (int)$pdo->query('SELECT COUNT(*) FROM slides')->fetchColumn();
$blogCount   = (int)$pdo->query('SELECT COUNT(*) FROM blog_entries')->fetchColumn();
$latestWeek  = $pdo->query('SELECT MAX(week) FROM blog_entries')->fetchColumn();

// Most recent activity timestamp across both tables
$lastSlide = $pdo->query('SELECT MAX(created_at) FROM slides')->fetchColumn();
$lastBlog  = $pdo->query('SELECT MAX(created_at) FROM blog_entries')->fetchColumn();

$lastActivity = null;
if ($lastSlide || $lastBlog) {
    $timestamps  = array_filter([$lastSlide, $lastBlog]);
    sort($timestamps);
    $lastActivity = date('M d, Y g:i A', strtotime(end($timestamps)));
}

echo json_encode([
    'success'       => true,
    'total_slides'  => $slideCount,
    'total_blogs'   => $blogCount,
    'latest_week'   => $latestWeek ? (int)$latestWeek : null,
    'last_updated'  => $lastActivity,
]);
