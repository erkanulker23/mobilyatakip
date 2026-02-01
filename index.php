<?php
/**
 * Valet: İstekleri NestJS uygulamasına (port 3000) yönlendirir.
 * NestJS'in çalışıyor olması gerekir: npm run start:dev
 */
$nodeUrl = 'http://127.0.0.1:3001';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$url = rtrim($nodeUrl, '/') . $requestUri;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => [
        'Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'application/json'),
        'Accept: ' . ($_SERVER['HTTP_ACCEPT'] ?? '*/*'),
        'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? ''),
        'X-Forwarded-Host: ' . ($_SERVER['HTTP_HOST'] ?? ''),
        'X-Forwarded-Proto: ' . (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')),
    ],
]);

if (in_array($method, ['POST', 'PUT', 'PATCH']) && ($input = file_get_contents('php://input'))) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode === 0) {
    $detail = $response === false ? ($curlError ?: 'Bağlantı kurulamadı') : 'HTTP yanıtı yok';
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'NestJS uygulamasına bağlanılamadı.',
        'hint' => 'Terminalde "npm run start:dev" ile uygulamayı başlatın (port 3001).',
        'detail' => $detail,
    ], JSON_UNESCAPED_UNICODE);
    return;
}

http_response_code($httpCode);
if ($contentType) {
    header('Content-Type: ' . $contentType);
}
echo $response;
