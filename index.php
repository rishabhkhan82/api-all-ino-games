<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Extract path from REQUEST_URI
$path = parse_url($requestUri, PHP_URL_PATH);
if ($path === null || $path === '') {
    $path = '/';
}

// Remove leading and trailing slashes
$path = trim($path, '/');

// Handle cases where index.php might be in a subdirectory on InfinityFree
// Remove 'backend/' if it appears in the path
$path = preg_replace('|^backend/|', '', $path);
// Remove 'index.php' if it appears in the path
$path = preg_replace('|^index\.php/|', '', $path);
$path = preg_replace('|^index\.php$|', '', $path);

// Clean up any double slashes or extra whitespace
$path = trim($path, '/');

try {
    if ($path === 'api/auth/login' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/auth.php';
        handleLogin();
    } elseif ($path === 'api/auth/logout' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/auth.php';
        handleLogout();
    } elseif ($path === 'api/auth/me' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/auth.php';
        handleMe();
    } elseif ($path === 'api/users' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/users.php';
        handleGetUsers();
    } elseif ($path === 'api/users' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/users.php';
        handleCreateUser();
    } elseif (preg_match('/^api\/users\/(\d+)$/', $path, $matches) && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/users.php';
        handleGetUser((int)$matches[1]);
    } elseif (preg_match('/^api\/users\/(\d+)$/', $path, $matches) && $requestMethod === 'PUT') {
        require_once __DIR__ . '/api/users.php';
        handleUpdateUser((int)$matches[1]);
    } elseif (preg_match('/^api\/users\/(\d+)$/', $path, $matches) && $requestMethod === 'DELETE') {
        require_once __DIR__ . '/api/users.php';
        handleDeleteUser((int)$matches[1]);
    } elseif ($path === 'api/public/game' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/games.php';
        handleGetCurrentGame();
    } elseif ($path === 'api/public/game/stream' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/games.php';
        handleGameStream();
    } elseif ($path === 'api/admin/game/start' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/games.php';
        handleStartGame();
    } elseif ($path === 'api/admin/game/end' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/games.php';
        handleEndGame();
    } elseif ($path === 'api/admin/game/setOpen' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/games.php';
        handleSetOpenNumber();
    } elseif ($path === 'api/admin/game/setClose' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/games.php';
        handleSetCloseNumber();
    } elseif ($path === 'api/admin/games' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/games.php';
        handleGetGames();
    } elseif ($path === 'api/games' && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/games.php';
        handleGetGames();
    } elseif ($path === 'api/games' && $requestMethod === 'POST') {
        require_once __DIR__ . '/api/games.php';
        handleCreateGame();
    } elseif (preg_match('/^api\/games\/(\d+)$/', $path, $matches) && $requestMethod === 'GET') {
        require_once __DIR__ . '/api/games.php';
        handleGetGame((int)$matches[1]);
    } elseif (preg_match('/^api\/games\/(\d+)$/', $path, $matches) && $requestMethod === 'PUT') {
        require_once __DIR__ . '/api/games.php';
        handleUpdateGame((int)$matches[1]);
    } elseif (preg_match('/^api\/games\/(\d+)$/', $path, $matches) && $requestMethod === 'DELETE') {
        require_once __DIR__ . '/api/games.php';
        handleDeleteGame((int)$matches[1]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
