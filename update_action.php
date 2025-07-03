<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $db->prepare("UPDATE feedback SET action_taken = ? WHERE id = ?");
    $stmt->execute([$_POST['action_taken'], $_POST['id']]);
}

header("Location: ../feedback.php");
exit();
?>