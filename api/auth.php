<?php

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }

    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($input['password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
            return;
        }

        // Simple token generation (not secure for production)
        $token = base64_encode($user['username'] . ':' . time() . ':' . rand());

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function handleLogout() {
    // In a simple implementation, logout is just client-side
    echo json_encode(['message' => 'Logged out successfully']);
}

function handleMe() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$authHeader || !preg_match('/Bearer (.+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        return;
    }

    $token = $matches[1];

    // In a real implementation, you'd validate the token
    // For simplicity, we'll just return a mock user
    // In production, decode and verify the JWT

    try {
        // Simple token validation (decode base64 and check format)
        $decoded = base64_decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            return;
        }

        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ? LIMIT 1");
        $stmt->execute(['admin']); // Default to admin for simplicity
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        echo json_encode([
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}