<?php
include 'db.php';
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    $sql->bind_result($id, $name, $hashed_password);

    if ($sql->fetch()) {
        if (password_verify($password, $hashed_password)) {
            $_SESSION['id'] = $id;
            $_SESSION['name'] = $name;
            header("Location: tasks.php"); // redirect to tasks dashboard
            exit;
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "Invalid Email";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .card { border-radius: 12px; width: 360px; }
        .form-control { margin-bottom: 15px; border-radius: 8px; }
        button { border-radius: 8px; }
        .error-msg { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 shadow">
    <h2 class="text-center mb-4">Login</h2>

    <?php if(!empty($error)) { ?>
        <div class="error-msg"><?= htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post" id="loginForm">
        <input type="email" class="form-control" name="email" placeholder="Email" required>
        <input type="password" class="form-control" name="password" placeholder="Password" required>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <p class="mt-3 text-center">
        New user? <a href="register.php">Register here</a>
    </p>
</div>

</body>
</html>
