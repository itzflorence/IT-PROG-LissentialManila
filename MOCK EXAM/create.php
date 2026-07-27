<?php
session_start();
require 'db.php';
$error = $success = '';

// check session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// capture logged in user ID
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    $title = $_POST['title'];
    $mood = $_POST['mood'];
    $content = $_POST['content'];
    $datetime = $_POST['entry-date'];

    if (empty($title) || empty($mood) || empty($content) || empty($datetime)) {
        $error = "All fields are required.";
    } else {
        // prepare & bind
        $sql = "INSERT INTO entries (user_id, title, mood, content, entry_date)
        VALUES (?, ?, ?, ?, ?)";  
        
        $stmt = mysqli_prepare($conn, $sql);
        $stmt->bind_param("issss", $user_id, $title, $mood, $content, $datetime);

        if ($stmt->execute()) {
            header("Location: read.php");
            exit();
        } else {
            $error = "Journal entry failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Entry</title>
</head>
<body>
    <form method="POST">
        <!-- TITLE -->
        <label>Title</label>
        <input type="text" name="title" required>
        
        <!-- MOOD -->
        <label>Mood</label>
        <select name="mood" required>
            <option value="Happy">Happy</option>
            <option value="Neutral">Neutral</option>
            <option value="Sad">Sad</option>
            <option value="Anxious">Anxious</option>
            <option value="Excited">Excited</option>
        </select>

        <!-- CONTENT -->
        <textarea name="content" required>Content</textarea>

        <!-- Entry Date -->
        <input type="date" name="entry-date" required>

        <button type="submit">Create Journal</button>
    </form>
</body>
</html>