<?php
declare(strict_types=1);
require_once __DIR__.'/game_stats.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    echo json_encode(['gamesPlayed' => gameCount()], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Show It counter: '.$error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Counter unavailable']);
}
