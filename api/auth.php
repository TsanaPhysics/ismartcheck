<?php
require_once 'helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'login') {
        $username = isset($input['username']) ? $input['username'] : '';
        $password = isset($input['password']) ? $input['password'] : '';
        
        $users = getJson('users.json');
        foreach ($users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                unset($user['password']);
                $_SESSION['user'] = $user;
                sendResponse(true, 'Login successful', $user);
            }
        }
        sendResponse(false, 'Invalid credentials');
    }
    
    if ($action === 'register') {
        $role = isset($input['role']) ? $input['role'] : 'student';
        $username = isset($input['username']) ? $input['username'] : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $firstname = isset($input['firstname']) ? $input['firstname'] : '';
        $lastname = isset($input['lastname']) ? $input['lastname'] : '';
        $major = isset($input['major']) ? $input['major'] : '';
        $year = isset($input['year']) ? $input['year'] : '';
        $secret = isset($input['secret']) ? $input['secret'] : '';
        
        if ($role === 'teacher') {
            if ($secret !== 'TEACHER2026') {
                sendResponse(false, 'รหัสลับสำหรับอาจารย์ไม่ถูกต้อง');
            }
            if (empty($username) || empty($password) || empty($firstname) || empty($lastname)) {
                sendResponse(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        } else {
            if (empty($username) || empty($password) || empty($firstname) || empty($lastname) || empty($major) || empty($year)) {
                sendResponse(false, 'กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        }
        
        $users = getJson('users.json');
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                sendResponse(false, 'Username นี้มีอยู่ในระบบแล้ว');
            }
        }
        
        $idPrefix = ($role === 'teacher') ? 't' : 's';
        
        $newUser = array(
            'id' => $idPrefix . time(),
            'username' => $username,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'major' => $major,
            'year' => $year,
            'name' => $firstname . ' ' . $lastname,
            'role' => $role
        );
        
        $users[] = $newUser;
        saveJson('users.json', $users);
        
        unset($newUser['password']);
        $_SESSION['user'] = $newUser;
        sendResponse(true, 'Registration successful', $newUser);
    }
}

if ($action === 'logout') {
    session_destroy();
    sendResponse(true, 'Logged out');
}

if ($action === 'me') {
    if (isset($_SESSION['user'])) {
        sendResponse(true, 'Authenticated', $_SESSION['user']);
    } else {
        sendResponse(false, 'Not authenticated');
    }
}

sendResponse(false, 'Invalid action');
