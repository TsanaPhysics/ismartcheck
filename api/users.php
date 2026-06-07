<?php
require_once 'helpers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$admin = requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $users = getJson('users.json');
        foreach ($users as &$u) {
            unset($u['password']);
        }
        sendResponse(true, 'Users fetched', $users);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'reset_password') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = isset($input['user_id']) ? $input['user_id'] : '';
        $newPassword = isset($input['new_password']) ? $input['new_password'] : '';
        
        if (empty($userId) || empty($newPassword)) {
            sendResponse(false, 'Missing data');
        }
        
        $users = getJson('users.json');
        $found = false;
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                $u['password'] = $newPassword;
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveJson('users.json', $users);
            sendResponse(true, 'Password updated');
        } else {
            sendResponse(false, 'User not found');
        }
    }
    
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = isset($input['user_id']) ? $input['user_id'] : '';
        
        $users = getJson('users.json');
        $newUsers = array();
        $found = false;
        
        foreach ($users as $u) {
            if ($u['id'] === $userId) {
                if ($u['role'] === 'superadmin') {
                    sendResponse(false, 'Cannot delete superadmin');
                }
                $found = true;
            } else {
                $newUsers[] = $u;
            }
        }
        
        if ($found) {
            saveJson('users.json', $newUsers);
            sendResponse(true, 'User deleted');
        } else {
            sendResponse(false, 'User not found');
        }
    }
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = isset($input['username']) ? $input['username'] : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $role = isset($input['role']) ? $input['role'] : 'student';
        
        if (empty($username) || empty($password)) {
            sendResponse(false, 'Username and password are required');
        }
        
        $users = getJson('users.json');
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                sendResponse(false, 'Username already exists');
            }
        }
        
        $newUser = array(
            'id' => 'u' . time(),
            'username' => $username,
            'password' => $password,
            'role' => $role
        );
        
        if ($role === 'student') {
            $newUser['firstname'] = isset($input['firstname']) ? $input['firstname'] : '';
            $newUser['lastname'] = isset($input['lastname']) ? $input['lastname'] : '';
            $newUser['major'] = isset($input['major']) ? $input['major'] : '';
            $newUser['year'] = isset($input['year']) ? $input['year'] : '';
        } else {
            $newUser['name'] = isset($input['name']) ? $input['name'] : '';
        }
        
        $users[] = $newUser;
        saveJson('users.json', $users);
        sendResponse(true, 'User created successfully');
    }
    
    if ($action === 'edit') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = isset($input['id']) ? $input['id'] : '';
        
        if (empty($userId)) sendResponse(false, 'Missing user ID');
        
        $users = getJson('users.json');
        $found = false;
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                if (isset($input['username'])) $u['username'] = $input['username'];
                if (isset($input['firstname'])) $u['firstname'] = $input['firstname'];
                if (isset($input['lastname'])) $u['lastname'] = $input['lastname'];
                if (isset($input['major'])) $u['major'] = $input['major'];
                if (isset($input['year'])) $u['year'] = $input['year'];
                if (isset($input['name'])) $u['name'] = $input['name'];
                if (isset($input['role'])) $u['role'] = $input['role'];
                $found = true;
                break;
            }
        }
        
        if ($found) {
            saveJson('users.json', $users);
            sendResponse(true, 'User updated successfully');
        } else {
            sendResponse(false, 'User not found');
        }
    }
}

sendResponse(false, 'Invalid action');
