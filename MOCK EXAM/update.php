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
    $id = (int) $_POST['id'];
    $title = trim($_POST['title']);
    $mood = trim($_POST['mood']);
    $content = trim($_POST['content']);
    $datetime = trim($_POST['entry-date']);

    // Validation
    if (empty($title) || empty($mood) || empty($content) || empty($datetime)) {
        $error = "All fields are required.";
    } else {
        // Secure Prepared Statement (Notice the table name is changed to entries to match your SELECT)
        $update = "UPDATE entries SET title = ?, mood = ?, content = ?, entry_date = ? WHERE id = ? AND user_id = ?";
        
        if ($stmt = mysqli_prepare($conn, $update)) {
            mysqli_stmt_bind_param($stmt, "ssssii", $title, $mood, $content, $datetime, $id, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: read.php");
                exit();
            } else {
                $error = "Error updating database: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Show edit form
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid entry ID.");
    }

    $id = (int) $_GET['id'];
    
    // Secure SELECT query ensuring user owns the entry
    $sql = "SELECT * FROM entries WHERE id = ? AND user_id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            die("Entry not found.");
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Journal Entry</title>
</head>
<body>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <!-- ID (hidden) -->
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
        
        <!-- TITLE (Fixed: changed from hidden to text) -->
        <label for="title">Title</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required><br><br>
        
        <!-- MOOD -->
        <label for="mood">Mood</label><br>
        <select id="mood" name="mood" required>
            <option value="Happy" <?php echo $row['mood'] === 'Happy' ? 'selected' : ''; ?>>Happy</option>
            <option value="Neutral" <?php echo $row['mood'] === 'Neutral' ? 'selected' : ''; ?>>Neutral</option>
            <option value="Sad" <?php echo $row['mood'] === 'Sad' ? 'selected' : ''; ?>>Sad</option>
            <option value="Anxious" <?php echo $row['mood'] === 'Anxious' ? 'selected' : ''; ?>>Anxious</option>
            <option value="Excited" <?php echo $row['mood'] === 'Excited' ? 'selected' : ''; ?>>Excited</option>
        </select><br><br>

        <label for="content">Content</label><br>
        <textarea id="content" name="content" required><?php echo htmlspecialchars($row['content']); ?></textarea><br><br>

        <!-- Entry Date -->
        <label for="entry-date">Entry Date</label><br>
        <input type="date" id="entry-date" name="entry-date" value="<?php echo htmlspecialchars($row['entry_date']); ?>" required><br><br>

        <button type="submit">Update Journal</button>
    </form>
</body>
</html>