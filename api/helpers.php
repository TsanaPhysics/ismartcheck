<?php
session_start();

function getJson($filename) {
    $path = __DIR__ . '/../data/' . $filename;
    if (!file_exists($path)) {
        return array();
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return $data ? $data : array();
}

function saveJson($filename, $data) {
    $path = __DIR__ . '/../data/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $data
    ));
    exit;
}

function requireAuth() {
    if (!isset($_SESSION['user'])) {
        sendResponse(false, 'Unauthorized');
    }
    return $_SESSION['user'];
}

function requireTeacher() {
    $user = requireAuth();
    if ($user['role'] !== 'teacher' && $user['role'] !== 'superadmin') {
        sendResponse(false, 'Forbidden: Teacher access required');
    }
    return $user;
}

function requireSuperAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'superadmin') {
        sendResponse(false, 'Forbidden: Super Admin access required');
    }
    return $user;
}
