<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

verifyCsrfToken();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid employee ID.');
}

$stmt = $pdo->prepare(
    "DELETE FROM employees WHERE id = :id"
);

$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 0) {
    header('Location: index.php');
    exit;
}

header('Location: index.php?deleted=1');
exit;
