<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'is_favorite' => false];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['file_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File ID not provided']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$file_id = (int)$_POST['file_id'];

// Check if the file is already a favorite
$stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND file_id = ?");
$stmt->bind_param("ii", $user_id, $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // It's a favorite, so remove it
    $delete_stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND file_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $file_id);
    if ($delete_stmt->execute()) {
        $response['success'] = true;
        $response['is_favorite'] = false;
    }
    $delete_stmt->close();
} else {
    // It's not a favorite, so add it
    $insert_stmt = $conn->prepare("INSERT INTO favorites (user_id, file_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $user_id, $file_id);
    if ($insert_stmt->execute()) {
        $response['success'] = true;
        $response['is_favorite'] = true;
    }
    $insert_stmt->close();
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
