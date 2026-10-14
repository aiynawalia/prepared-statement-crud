<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

$errors = [];

$name = '';
$email = '';
$department = '';
$salary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Name is required and must be at most 100 bytes.';
    }

    if (
        $email === '' ||
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Enter a valid email address (maximum 150 bytes).';
    }

    if ($department === '' || strlen($department) > 100) {
        $errors[] = 'Department is required.';
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
            $sql = "INSERT INTO employees
                    (name, email, department, salary)
                    VALUES (:name, :email, :department, :salary)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':department' => $department,
                ':salary' => number_format((float) $salary, 2, '.', '')
            ]);

            header('Location: index.php?created=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'This email may already exist.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'Unable to save the employee.';
            }
        }
    }
}

$stmt = $pdo->query(
    "SELECT id, name, email, department, salary, created_at
     FROM employees
     ORDER BY id DESC"
);

$employees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Management</title>
</head>

<body>

    <h1>Employee Management System</h1>

    <?php if (isset($_GET['created'])): ?>
        <p>Employee added successfully.</p>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <p>Employee updated successfully.</p>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <p>Employee deleted successfully.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p><?= e($error) ?></p>
    <?php endforeach; ?>

    <h2>Add Employee</h2>

    <form method="POST" action="index.php">
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

        <button type="submit">Add Employee</button>
    </form>

    <h2>Employee List</h2>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Salary</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?= e($employee['id']) ?></td>
                    <td><?= e($employee['name']) ?></td>
                    <td><?= e($employee['email']) ?></td>
                    <td><?= e($employee['department']) ?></td>
                    <td><?= e($employee['salary']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= (int) $employee['id'] ?>">
                            Edit
                        </a>

                        <form method="POST" action="delete.php"
                            style="display:inline">
                            <input type="hidden" name="csrf_token"
                                value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id"
                                value="<?= (int) $employee['id'] ?>">
                            <button type="submit"
                                onclick="return confirm('Delete this employee?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>