<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) header("Location: login.php");

$user_id = $_SESSION['id'];
$user_email = $_SESSION['email'] ?? ''; 
$username = $_SESSION['name'] ?? 'User';

// --- Fetch Profile Image by Email (अब काम का नहीं लेकिन future use के लिए रहने दिया है) ---
$profile_img = 'task_manager/uploads/default.png'; 
if(!empty($user_email)){
    $stmt = $conn->prepare("SELECT photo FROM users WHERE email=? LIMIT 1"); 
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        if(!empty($row['photo'])){
            $profile_img = "task_manager/uploads/" . $row['photo']; 
        }
    }
    $stmt->close();
}

// --- Add or Update Task ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $due = !empty($_POST['due_date']) ? $conn->real_escape_string($_POST['due_date']) : null;

    if (!empty($_POST['task_id'])) {
        $task_id = (int)$_POST['task_id'];
        $conn->query("UPDATE tasks SET title='$title', description='$desc', due_date='$due' WHERE id=$task_id");
    } else {
        $conn->query("INSERT INTO tasks (title, description, due_date, status) VALUES ('$title', '$desc', '$due', 'Pending')");
    }
    header("Location: tasks.php");
    exit;
}

// --- Delete Task ---
if (isset($_GET['delete'])) {
    $task_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tasks WHERE id=$task_id");
    header("Location: tasks.php");
    exit;
}

// --- Mark Complete ---
if (isset($_GET['complete'])) {
    $task_id = (int)$_GET['complete'];
    $conn->query("UPDATE tasks SET status='Completed' WHERE id=$task_id");
    header("Location: tasks.php");
    exit;
}

// --- Edit Task ---
$edit_task = null;
if (isset($_GET['edit'])) {
    $task_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM tasks WHERE id=$task_id");
    $edit_task = $edit_result->fetch_assoc();
}

// --- Fetch Tasks ---
$tasks = $conn->query("SELECT * FROM tasks ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Tasks</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 70px; }
    .error { color: red; font-size: 0.9em; margin-bottom: 5px; }
    input, textarea { margin-bottom: 5px; }
    .btn-status { width: 80px; }
</style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid d-flex align-items-center">
        <!-- केवल Welcome Text -->
        <span class="navbar-text text-white">
            Welcome, <?= htmlspecialchars($username) ?>
        </span>

        <div class="ms-auto">
            <a href="profile.php" class="btn btn-outline-light btn-sm me-2">Profile</a>
            <a href="tasks.php" class="btn btn-outline-light btn-sm me-2">Manage Tasks</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card p-4 shadow">
        <h2 class="text-center mb-3">Your Tasks</h2>

        <!-- Task Form -->
        <form id="taskForm" method="post">
            <input type="hidden" name="task_id" value="<?= $edit_task['id'] ?? '' ?>">

            <input type="text" name="title" placeholder="Title" class="form-control" value="<?= $edit_task['title'] ?? '' ?>">
            <div class="error" id="titleError"></div>

            <textarea name="description" placeholder="Description" class="form-control"><?= $edit_task['description'] ?? '' ?></textarea>
            <div class="error" id="descError"></div>

            <input type="date" name="due_date" class="form-control" value="<?= $edit_task['due_date'] ?? '' ?>">
            <div class="error" id="dateError"></div>

            <button type="submit" class="btn btn-primary mt-2"><?= $edit_task ? 'Update Task' : 'Add Task' ?></button>
        </form>

        <!-- Task Table -->
        <table class="table table-bordered table-striped text-center mt-4">
            <thead class="table-dark">
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tasks && $tasks->num_rows > 0) { 
                    while ($row = $tasks->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= $row['due_date'] ?></td>
                            <td><?= $row['status'] ?></td>
                            <td>
                                <?php if($row['status']=='Pending'){ ?>
                                    <a href="?complete=<?= $row['id'] ?>" class="btn btn-success btn-sm btn-status">Complete</a>
                                <?php } ?>
                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-info btn-sm btn-status">Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this task?')" class="btn btn-danger btn-sm btn-status">Delete</a>
                            </td>
                        </tr>
                <?php } } else { ?>
                    <tr><td colspan="5">No tasks found.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('taskForm').addEventListener('submit', function(e){
    let valid = true;
    document.getElementById('titleError').innerText = '';
    document.getElementById('descError').innerText = '';
    document.getElementById('dateError').innerText = '';

    let title = this.title.value.trim();
    let desc = this.description.value.trim();
    let date = this.due_date.value.trim();

    if(title==''){ document.getElementById('titleError').innerText = 'Please fill this field'; valid=false; }
    if(desc==''){ document.getElementById('descError').innerText = 'Please fill this field'; valid=false; }
    if(date==''){ document.getElementById('dateError').innerText = 'Please select a date'; valid=false; }

    if(!valid) e.preventDefault();
});
</script>

</body>
</html>
