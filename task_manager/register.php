<?php
include 'db.php';
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST["password"];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        $error = "Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sql = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $sql->bind_param("sss", $name, $email, $hashedPassword);

            if ($sql->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Something went wrong, please try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
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
    <h2 class="text-center mb-4">Register</h2>

    <?php if(!empty($error)) { ?>
        <div class="error-msg"><?= htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post">
        <input type="text" class="form-control" name="name" placeholder="Full Name" required>
        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
        <input type="password" class="form-control" name="password" placeholder="Password" required>
        <input type="password" class="form-control" name="cpassword" placeholder="Confirm Password" required>
        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="mt-3 text-center">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>
