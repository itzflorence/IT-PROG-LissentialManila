<?php
session_start();
require 'db.php';

$result = mysqli_query($conn, "SELECT * FROM entries");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entries</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a class="logout" href="logout.php">Logout</a><br><br>
    <?php
    while ($row = mysqli_fetch_object($result)) {
    ?>
        <div class="entry-box">
            Title: <?php echo $row->title ?> <br>
            Mood: <?php echo $row->mood ?> <br>
            Content: <?php echo $row->content ?> <br>
            Date: <?php echo $row->entry_date ?> <br>
            <a href="update.php?id=<?php echo $row->id; ?> ">Edit</a><br>
            <a href="delete.php?id= <?php echo $row->id ?> " onclick="return confirm('Are you sure you want to delete this?');">Delete</a>
        </div>

        <br>
    <?php
    }
    ?>

    <a class="create-entry" href="create.php">Create Entry</a>
</body>
</html>