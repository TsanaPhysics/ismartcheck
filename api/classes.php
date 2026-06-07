<?php
require_once 'helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $user = requireAuth();
        $classes = getJson('classes.json');
        
        if ($user['role'] === 'superadmin') {
            sendResponse(true, 'Classes fetched', $classes);
        } else if ($user['role'] === 'teacher') {
            $myClasses = array_filter($classes, function($c) use ($user) {
                return isset($c['teacher_id']) && $c['teacher_id'] === $user['id'];
            });
            sendResponse(true, 'Classes fetched', array_values($myClasses));
        } else {
            sendResponse(true, 'Classes fetched', $classes);
        }
    }
    
    if ($action === 'get') {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $classes = getJson('classes.json');
        foreach ($classes as $c) {
            if (isset($c['id']) && $c['id'] === $id) {
                sendResponse(true, 'Class fetched', $c);
            }
        }
        sendResponse(false, 'Class not found');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireAuth();
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $name = isset($input['name']) ? $input['name'] : '';
        $code = isset($input['code']) ? $input['code'] : '';
        
        if (empty($name)) {
            sendResponse(false, 'Class name is required');
        }
        
        $classes = getJson('classes.json');
        $newClass = array(
            'id' => 'c' . time(),
            'code' => $code,
            'name' => $name,
            'teacher_id' => $user['id'],
            'created_at' => date('Y-m-d H:i:s')
        );
        
        $classes[] = $newClass;
        saveJson('classes.json', $classes);
        sendResponse(true, 'Class created', $newClass);
    }
    
    if ($action === 'edit') {
        $id = isset($input['id']) ? $input['id'] : '';
        $name = isset($input['name']) ? $input['name'] : '';
        $code = isset($input['code']) ? $input['code'] : '';
        
        if (empty($id) || empty($name)) {
            sendResponse(false, 'Missing class ID or name');
        }
        
        $classes = getJson('classes.json');
        $found = false;
        foreach ($classes as &$c) {
            if ($c['id'] === $id) {
                if ($user['role'] !== 'superadmin' && $c['teacher_id'] !== $user['id']) {
                    sendResponse(false, 'Forbidden: Not your class');
                }
                $c['name'] = $name;
                $c['code'] = $code;
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveJson('classes.json', $classes);
            sendResponse(true, 'Class updated');
        } else {
            sendResponse(false, 'Class not found');
        }
    }
    
    if ($action === 'delete') {
        $id = isset($input['id']) ? $input['id'] : '';
        if (empty($id)) sendResponse(false, 'Missing class ID');
        
        $classes = getJson('classes.json');
        $found = false;
        foreach ($classes as $i => $c) {
            if ($c['id'] === $id) {
                if ($user['role'] !== 'superadmin' && $c['teacher_id'] !== $user['id']) {
                    sendResponse(false, 'Forbidden: Not your class');
                }
                array_splice($classes, $i, 1);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveJson('classes.json', $classes);
            
            // Delete associated attendance
            $attendance = getJson('attendance.json');
            $newAttendance = array();
            foreach ($attendance as $a) {
                if (isset($a['class_id']) && $a['class_id'] !== $id) {
                    $newAttendance[] = $a;
                }
            }
            saveJson('attendance.json', $newAttendance);
            
            sendResponse(true, 'Class deleted successfully');
        } else {
            sendResponse(false, 'Class not found');
        }
    }
}

sendResponse(false, 'Invalid action');
