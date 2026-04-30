<?php

if (getConfig('app.env') === 'development') {
    header('Access-Control-Allow-Origin: *');
} else {
    $corsConfig = getConfig('cors');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $corsConfig['allowed_origins'])) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
}

$corsConfig = getConfig('cors');
header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
header('Access-Control-Expose-Headers: ' . implode(', ', $corsConfig['exposed_headers']));
header('Access-Control-Max-Age: ' . $corsConfig['max_age']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

function getIntQuery(string $name, ?int $default = null): ?int {
    if (!isset($_GET[$name]) || $_GET[$name] === '') {
        return $default;
    }
    $value = filter_var($_GET[$name], FILTER_VALIDATE_INT);
    return $value === false ? $default : $value;
}

function getGameTypeName(int $type): string {
    switch ($type) {
        case 1:
            return 'Orange City Day';
        case 2:
            return 'Orange City Night';
        default:
            return 'Unknown Game';
    }
}

function getGameTypeFromInput(array $input): int {
    if (isset($input['game_type'])) {
        $type = filter_var($input['game_type'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($type !== false) {
            return $type;
        }
    }
    return 1;
}

function buildGameSelectFields(): string {
    return 'id, name, game_type AS gameType, active, open_time, open_number, close_time, close_number, final_number, created_at';
}

function findActiveGame(PDO $pdo, ?int $gameType = null): ?array {
    $sql = 'SELECT id FROM games WHERE active = 1';
    $params = [];
    if ($gameType !== null) {
        $sql .= ' AND game_type = ?';
        $params[] = $gameType;
    }
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    return $game ?: null;
}

function fetchCurrentGame(PDO $pdo, ?int $gameType = null): ?array {
    $sql = 'SELECT ' . buildGameSelectFields() . ' FROM games WHERE active = 1';
    $params = [];
    if ($gameType !== null) {
        $sql .= ' AND game_type = ?';
        $params[] = $gameType;
    }
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    return $game ?: null;
}

function fetchGamesHistory(PDO $pdo, ?int $gameType = null, ?string $date = null): array {
    $sql = 'SELECT ' . buildGameSelectFields() . ' FROM games';
    $params = [];
    $conditions = [];

    if ($gameType !== null) {
        $conditions[] = 'game_type = ?';
        $params[] = $gameType;
    }

    if ($date !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
        }
        $conditions[] = 'DATE(created_at) = ?';
        $params[] = $date;
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sendSseEvent(string $event, array $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    @ob_flush();
    @flush();
}

function handleGameStream(): void {
    ignore_user_abort(true);
    set_time_limit(0);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    if (ob_get_level()) {
        ob_end_flush();
    }

    $gameType = getIntQuery('type', null);
    $date = isset($_GET['date']) ? trim($_GET['date']) : null;
    if ($date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
    }

    $lastPayload = null;
    while (!connection_aborted()) {
        $pdo = getDBConnection();
        $currentGame = fetchCurrentGame($pdo, $gameType);
        $games = fetchGamesHistory($pdo, $gameType, $date);
        $payload = ['currentGame' => $currentGame, 'games' => $games];
        $payloadJson = json_encode($payload);

        if ($payloadJson !== $lastPayload) {
            sendSseEvent('gameUpdate', $payload);
            $lastPayload = $payloadJson;
        }

        sleep(2);
    }
    exit;
}

function handleGetGames(): void {
    $pdo = getDBConnection();
    $gameType = getIntQuery('type', null);
    $date = isset($_GET['date']) ? trim($_GET['date']) : null;
    $sql = 'SELECT ' . buildGameSelectFields() . ' FROM games';
    $params = [];

    $conditions = [];
    if ($gameType !== null) {
        $conditions[] = 'game_type = ?';
        $params[] = $gameType;
    }

    if ($date !== null) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
        }
        $conditions[] = 'DATE(created_at) = ?';
        $params[] = $date;
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($games);
}

function handleCreateGame(): void {
    $input = getJsonInput();
    $gameType = getGameTypeFromInput($input);
    $name = isset($input['name']) ? trim($input['name']) : getGameTypeName($gameType);
    $active = isset($input['active']) ? (int)(bool)$input['active'] : 0;
    $openNumber = $input['open_number'] ?? null;
    $closeNumber = $input['close_number'] ?? null;

    try {
        $pdo = getDBConnection();
        if ($active && findActiveGame($pdo, $gameType)) {
            jsonResponse(['error' => 'A game of this type is already active'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO games (name, active, open_number, close_number, game_type) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $active, $openNumber, $closeNumber, $gameType]);
        $id = (int)$pdo->lastInsertId();
        jsonResponse(['message' => 'Game created', 'id' => $id], 201);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleGetGame(int $id): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT ' . buildGameSelectFields() . ' FROM games WHERE id = ?');
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        jsonResponse(['error' => 'Game not found'], 404);
    }

    jsonResponse($game);
}

function handleGetCurrentGame(): void {
    $pdo = getDBConnection();
    $gameType = getIntQuery('type', null);
    $sql = 'SELECT ' . buildGameSelectFields() . ' FROM games WHERE active = 1';
    $params = [];

    if ($gameType !== null) {
        $sql .= ' AND game_type = ?';
        $params[] = $gameType;
    }

    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        jsonResponse(null);
    }

    jsonResponse($game);
}

function handleStartGame(): void {
    $gameType = getIntQuery('type', 1);
    $pdo = getDBConnection();
    if (findActiveGame($pdo, $gameType)) {
        jsonResponse(['error' => 'A game of this type is already active'], 400);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO games (name, active, game_type, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([getGameTypeName($gameType), 1, $gameType]);
        $id = (int)$pdo->lastInsertId();
        handleGetGame($id);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleEndGame(): void {
    $gameType = getIntQuery('type', null);

    $pdo = getDBConnection();
    $game = findActiveGame($pdo, $gameType);

    if (!$game) {
        jsonResponse(['error' => 'No active game to end'], 404);
    }

    try {
        $stmt = $pdo->prepare('UPDATE games SET active = 0 WHERE id = ?');
        $stmt->execute([$game['id']]);
        handleGetGame((int)$game['id']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleSetOpenNumber(): void {
    $number = $_GET['number'] ?? null;
    $gameType = getIntQuery('type', null);

    if (!$number) {
        jsonResponse(['error' => 'Number is required'], 400);
    }

    $pdo = getDBConnection();
    $game = findActiveGame($pdo, $gameType);

    if (!$game) {
        jsonResponse(['error' => 'No active game found'], 404);
    }

    try {
        $stmt = $pdo->prepare('UPDATE games SET open_number = ?, open_time = NOW() WHERE id = ?');
        $stmt->execute([$number, $game['id']]);
        handleGetGame((int)$game['id']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleSetCloseNumber(): void {
    $number = $_GET['number'] ?? null;
    $gameType = getIntQuery('type', null);

    if (!$number) {
        jsonResponse(['error' => 'Number is required'], 400);
    }

    $pdo = getDBConnection();
    $game = findActiveGame($pdo, $gameType);

    if (!$game) {
        jsonResponse(['error' => 'No active game found'], 404);
    }

    try {
        $stmt = $pdo->prepare('UPDATE games SET close_number = ?, close_time = NOW() WHERE id = ?');
        $stmt->execute([$number, $game['id']]);
        handleGetGame((int)$game['id']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleSetFinalNumber(): void {
    $number = $_GET['number'] ?? null;
    $gameType = getIntQuery('type', null);

    if (!$number) {
        jsonResponse(['error' => 'Number is required'], 400);
    }

    $pdo = getDBConnection();
    $game = findActiveGame($pdo, $gameType);

    if (!$game) {
        jsonResponse(['error' => 'No active game found'], 404);
    }

    try {
        $stmt = $pdo->prepare('UPDATE games SET final_number = ? WHERE id = ?');
        $stmt->execute([$number, $game['id']]);
        handleGetGame((int)$game['id']);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error'], 500);
    }
}

function handleUpdateGame(int $id): void {
    $input = getJsonInput();
    $fields = [];
    $params = [];

    if (isset($input['name'])) {
        $fields[] = 'name = ?';
        $params[] = trim($input['name']);
    }
    if (isset($input['active'])) {
        $fields[] = 'active = ?';
        $params[] = (int)(bool)$input['active'];
    }
    if (array_key_exists('open_number', $input)) {
        $fields[] = 'open_number = ?';
        $params[] = $input['open_number'];
    }
    if (array_key_exists('close_number', $input)) {
        $fields[] = 'close_number = ?';
        $params[] = $input['close_number'];
    }
    if (array_key_exists('game_type', $input)) {
        $fields[] = 'game_type = ?';
        $params[] = (int)$input['game_type'];
    }

    if (empty($fields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $params[] = $id;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('UPDATE games SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Game not found or no changes made'], 404);
    }

    jsonResponse(['message' => 'Game updated']);
}

function handleDeleteGame(int $id): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('DELETE FROM games WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'Game not found'], 404);
    }

    jsonResponse(['message' => 'Game deleted']);
}
