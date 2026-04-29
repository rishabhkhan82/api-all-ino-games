<?php

function getJsonInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function handleGetUsers(): void {
    $pdo = getDBConnection();
    $stmt = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['data' => $users]);
}

function handleCreateUser(): void {
    $input = getJsonInput();

    if (empty($input['username']) || empty($input['password'])) {
        jsonResponse(['error' => 'Username and password are required'], 400);
    }

    $username = trim($input['username']);
    $password = password_hash($input['password'], PASSWORD_DEFAULT);
    $role = isset($input['role']) ? trim($input['role']) : 'OWNER';

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $password, $role]);

        $id = (int)$pdo->lastInsertId();
        jsonResponse(['message' => 'User created', 'id' => $id], 201);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] === 1062) {
            jsonResponse(['error' => 'Username already exists'], 409);
        }
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleGetUser(int $id): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT id, username, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    jsonResponse(['data' => $user]);
}

function handleUpdateUser(int $id): void {
    $input = getJsonInput();
    $fields = [];
    $params = [];

    if (isset($input['username'])) {
        $fields[] = 'username = ?';
        $params[] = trim($input['username']);
    }

    if (isset($input['password'])) {
        $fields[] = 'password = ?';
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    if (isset($input['role'])) {
        $fields[] = 'role = ?';
        $params[] = trim($input['role']);
    }

    if (empty($fields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $params[] = $id;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'User not found or no changes made'], 404);
    }

    jsonResponse(['message' => 'User updated']);
}

function handleDeleteUser(int $id): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    jsonResponse(['message' => 'User deleted']);
}
