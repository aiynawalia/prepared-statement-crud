<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid employee ID.');
}

$stmt = $pdo->prepare(
    "SELECT id, name, email, department, salary
     FROM employees WHERE id = :id"
);
$stmt->execute([':id' => $id]);

$employee = $stmt->fetch();

if (!$employee) {
    http_response_code(404);
    exit('Employee not found.');
}

$errors = [];

$name = $employee['name'];
$email = $employee['email'];
$department = $employee['department'];
$salary = $employee['salary'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Enter a name of at most 100 bytes.';
    }

    if (
        $email === '' ||
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($department === '' || strlen($department) > 100) {
        $errors[] = 'Enter a valid department.';
    }

    if (
        $salary === '' ||
        !is_numeric($salary) ||
        (float) $salary < 0 ||
        (float) $salary > 99999999.99
    ) {
        $errors[] = 'Enter a valid non-negative salary.';
    }

    if (!$errors) {
        try {
            $sql = "UPDATE employees
                    SET name = :name,
                        email = :email,
                        department = :department,
                        salary = :salary
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':department' => $department,
                ':salary' => number_format((float) $salary, 2, '.', ''),
                ':id' => $id
            ]);

            header('Location: index.php?updated=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'This email may already be in use.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'Unable to update the employee.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Employee</title>
</head>

<body>

    <h1>Edit Employee</h1>

    <?php foreach ($errors as $error): ?>
        <p><?= e($error) ?></p>
    <?php endforeach; ?>

    <form method="POST"
        action="edit.php?id=<?= (int) $id ?>">

        <input type="hidden" name="csrf_token"
            value="<?= e(csrfToken()) ?>">

        <label>Name:</label>
        <input type="text" name="name" maxlength="100"
            value="<?= e($name) ?>" required>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" maxlength="150"
            value="<?= e($email) ?>" required>
        <br><br>

        <label>Department:</label>
        <input type="text" name="department" maxlength="100"
            value="<?= e($department) ?>" required>
        <br><br>

        <label>Salary:</label>
        <input type="number" name="salary" min="0"
            max="99999999.99" step="0.01"
            value="<?= e($salary) ?>" required>
        <br><br>

        <button type="submit">Update Employee</button>
        <a href="index.php">Cancel</a>
    </form>

</body>

</html>