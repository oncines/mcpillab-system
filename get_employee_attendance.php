<?php
require_once 'config.php';

// Admin-only endpoint
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
$limit   = min(intval($_GET['limit'] ?? 15), 100); // cap at 100

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

global $pdo;

try {
    /*
     * Adjust column names below to match your actual `attendance` table.
     * Common column names are listed as alternatives in comments.
     *
     * Assumed schema:
     *   attendance (id, user_id, date, time_in, time_out, total_hours, status)
     *
     * If your table uses different names (e.g. clock_in / clock_out,
     * check_in_time / check_out_time, work_date, etc.) update accordingly.
     */
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(a.date, '%d %b %Y')         AS date,
            TIME_FORMAT(a.time_in,  '%h:%i %p')     AS time_in,
            TIME_FORMAT(a.time_out, '%h:%i %p')     AS time_out,
            a.total_hours,
            a.status
        FROM attendance a
        WHERE a.user_id = :user_id
        ORDER BY a.date DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':lim',     $limit,   PDO::PARAM_INT);
    $stmt->execute();

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'records' => $records]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}