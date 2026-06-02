<?php
// admin/api/export_users.php - PHASE 11 PROFESSOR'S VERSION

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d') . '.csv');

$stmt = $pdo->query("SELECT id, username, email, nickname, is_admin, banned, created_at FROM users ORDER BY id");
$users = $stmt->fetchAll();

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Username', 'Email', 'Nickname', 'Admin', 'Banned', 'Joined']);

foreach ($users as $user) {
    fputcsv($output, [
        $user['id'],
        $user['username'],
        $user['email'],
        $user['nickname'] ?? '',
        $user['is_admin'] ? 'Yes' : 'No',
        $user['banned'] ? 'Yes' : 'No',
        $user['created_at']
    ]);
}

fclose($output);
exit;
?>