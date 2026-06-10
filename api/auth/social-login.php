<?php
require_once '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  die(json_encode(['message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$provider = strtolower(trim($data['provider'] ?? ''));

if (!in_array($provider, ['google', 'facebook'], true)) {
  http_response_code(400);
  die(json_encode(['message' => 'Invalid provider']));
}

// OAuth requires real credentials and redirect/callback URLs.
// This endpoint is a stub to wire up the frontend buttons.
http_response_code(501);
die(json_encode([
  'message' => 'Social login is not configured yet. Add OAuth credentials for ' . $provider . '.'
]));

