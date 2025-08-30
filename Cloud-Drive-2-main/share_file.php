<?php
session_start();
include 'db.php';
$user_id = $_SESSION['user_id'];
$file_id = $_POST['file_id'];
$recipient_email = $_POST['recipient_email'];

// Find recipient's user ID
$stmt_recipient = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt_recipient->bind_param("s", $recipient_email);
$stmt_recipient->execute();
$result_recipient = $stmt_recipient->get_result();

if ($result_recipient->num_rows === 0) {
    $_SESSION['error'] = 'Recipient user not found.';
    header('Location: dashboard.php');
    exit;
}

$recipient = $result_recipient->fetch_assoc();
$recipient_id = $recipient['id'];

// Insert into shared_files table
$stmt_share = $conn->prepare("INSERT INTO shared_files (file_id, shared_by_user_id, shared_to_user_id) VALUES (?, ?, ?)");
$stmt_share->bind_param("iii", $file_id, $user_id, $recipient_id);

if ($stmt_share->execute()) {
    $_SESSION['success'] = 'File shared successfully!';
} else {
    $_SESSION['error'] = 'Error sharing file.';
}

header('Location: dashboard.php');
exit;
?>