<?php
require_once 'helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark') {
        $student = requireAuth();
        if ($student['role'] !== 'student') {
            sendResponse(false, 'Only students can mark attendance');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $classId = isset($input['class_id']) ? $input['class_id'] : '';
        $sessionToken = isset($input['session_token']) ? $input['session_token'] : '';
        
        if (empty($classId) || empty($sessionToken)) {
            sendResponse(false, 'Invalid QR code data');
        }
        
        $attendance = getJson('attendance.json');
        
        foreach ($attendance as $a) {
            if (isset($a['student_id']) && $a['student_id'] === $student['id'] && isset($a['class_id']) && $a['class_id'] === $classId && isset($a['session_token']) && $a['session_token'] === $sessionToken) {
                sendResponse(false, 'Attendance already marked for this session');
            }
        }
        
        $record = array(
            'id' => 'a' . time() . rand(100, 999),
            'student_id' => $student['id'],
            'student_name' => $student['name'],
            'class_id' => $classId,
            'session_token' => $sessionToken,
            'timestamp' => date('Y-m-d H:i:s')
        );
        
        $attendance[] = $record;
        saveJson('attendance.json', $attendance);
        
        sendResponse(true, 'Attendance marked successfully!');
    } else if ($action === 'mark_ai') {
        // For AI Scan Kiosk, we might not have a student session.
        $input = json_decode(file_get_contents('php://input'), true);
        $studentId = isset($input['student_id']) ? $input['student_id'] : '';
        $classId = isset($input['class_id']) ? $input['class_id'] : 'AI_SCAN_01'; // Default class for Kiosk if none provided
        $sessionToken = isset($input['session_token']) ? $input['session_token'] : date('Ymd');
        
        if (empty($studentId)) {
            sendResponse(false, 'Missing student_id');
        }
        
        $attendance = getJson('attendance.json');
        
        // Prevent duplicate marking within the same minute or same session
        foreach ($attendance as $a) {
            if (isset($a['student_id']) && $a['student_id'] === $studentId && isset($a['class_id']) && $a['class_id'] === $classId && isset($a['session_token']) && $a['session_token'] === $sessionToken) {
                sendResponse(false, 'Attendance already marked');
            }
        }
        
        $users = getJson('users.json');
        $studentName = 'Unknown';
        foreach ($users as $u) {
            if ($u['id'] === $studentId) {
                $studentName = $u['firstname'] ? $u['firstname'] . ' ' . $u['lastname'] : $u['name'];
                break;
            }
        }
        
        $record = array(
            'id' => 'ai' . time() . rand(100, 999),
            'student_id' => $studentId,
            'student_name' => $studentName,
            'class_id' => $classId,
            'session_token' => $sessionToken,
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'AI_SCAN'
        );
        
        $attendance[] = $record;
        saveJson('attendance.json', $attendance);
        
        sendResponse(true, 'AI Attendance marked successfully!');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'report') {
        $user = requireAuth();
        $classId = isset($_GET['class_id']) ? $_GET['class_id'] : '';
        
        $attendance = getJson('attendance.json');
        $filtered = array();
        
        if ($user['role'] === 'superadmin') {
            if (!empty($classId)) {
                $filtered = array_filter($attendance, function($a) use ($classId) {
                    return isset($a['class_id']) && $a['class_id'] === $classId;
                });
            } else {
                $filtered = $attendance;
            }
        } else if ($user['role'] === 'teacher') {
            $filtered = array_filter($attendance, function($a) use ($classId) {
                return isset($a['class_id']) && $a['class_id'] === $classId;
            });
        } else {
            $filtered = array_filter($attendance, function($a) use ($user) {
                return isset($a['student_id']) && $a['student_id'] === $user['id'];
            });
        }
        
        usort($filtered, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        $usersMap = array();
        $allUsers = getJson('users.json');
        foreach ($allUsers as $u) {
            $usersMap[$u['id']] = $u;
        }

        $enriched = array();
        foreach ($filtered as $f) {
            if (isset($usersMap[$f['student_id']])) {
                $u = $usersMap[$f['student_id']];
                $f['student_code'] = isset($u['username']) ? $u['username'] : '-';
                $f['firstname'] = isset($u['firstname']) ? $u['firstname'] : '-';
                $f['lastname'] = isset($u['lastname']) ? $u['lastname'] : '-';
                $f['major'] = isset($u['major']) ? $u['major'] : '-';
                $f['year'] = isset($u['year']) ? $u['year'] : '-';
            } else {
                $f['student_code'] = '-';
                $f['firstname'] = $f['student_name'];
                $f['lastname'] = '';
                $f['major'] = '-';
                $f['year'] = '-';
            }
            $enriched[] = $f;
        }
        
        sendResponse(true, 'Report fetched', array_values($enriched));
    }
}

sendResponse(false, 'Invalid action');
