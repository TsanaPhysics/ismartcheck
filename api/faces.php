<?php
require_once 'helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method Not Allowed');
}

// Get POST body
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';

if ($action === 'save') {
    requireTeacher(); // Only teachers can save faces

    $studentId = isset($input['student_id']) ? $input['student_id'] : '';
    $descriptor = isset($input['descriptor']) ? $input['descriptor'] : null;

    if (empty($studentId) || empty($descriptor)) {
        sendResponse(false, 'Missing required fields');
    }

    $faces = getJson('faces.json');
    
    // Check if student already has a face registered
    $found = false;
    foreach ($faces as &$face) {
        if ($face['student_id'] === $studentId) {
            $face['descriptor'] = $descriptor; // Update
            $found = true;
            break;
        }
    }

    if (!$found) {
        $faces[] = array(
            'student_id' => $studentId,
            'descriptor' => $descriptor
        );
    }

    saveJson('faces.json', $faces);
    sendResponse(true, 'Face registered successfully');

} else if ($action === 'load_all') {
    // This can be public or require auth depending on security needs.
    // For kiosk mode, it might be open or require teacher.
    $faces = getJson('faces.json');
    sendResponse(true, 'Faces loaded', $faces);

} else {
    sendResponse(false, 'Invalid action');
}
