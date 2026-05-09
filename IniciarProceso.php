<?php

declare(strict_types=1);

/**
 * ============================================================
 * CONFIGURACIÓN
 * ============================================================
 */

// URL base de Flowable
$FLOWABLE_BASE_URL = 'http://localhost:7070/flowable-rest';

// Endpoint para iniciar procesos
$FLOWABLE_PROCESS_ENDPOINT = '/service/runtime/process-instances';

// Credenciales Basic Auth
$FLOWABLE_USERNAME = 'rest-admin';
$FLOWABLE_PASSWORD = 'test';

// Process Definition Key
$FLOWABLE_PROCESS_DEFINITION_KEY = 'fundacionInscripcionSocio';

// Timeout CURL
$CURL_TIMEOUT = 30;


/**
 * ============================================================
 * HEADERS
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');


/**
 * ============================================================
 * VALIDAR MÉTODO
 * ============================================================
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);

    exit;
}


/**
 * ============================================================
 * OBTENER DATOS
 * ============================================================
 */

$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$monto = (int)($_POST['monto'] ?? 0);
$diaMes = (int)($_POST['diaMes'] ?? 0);


/**
 * ============================================================
 * VALIDACIONES BÁSICAS
 * ============================================================
 */

$errores = [];

if ($rut === '') {
    $errores[] = 'El RUT es obligatorio';
}

if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio';
}

if ($monto <= 0) {
    $errores[] = 'El monto debe ser mayor a 0';
}

if ($diaMes < 1 || $diaMes > 31) {
    $errores[] = 'El día del mes es inválido';
}

if (!empty($errores)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Errores de validación',
        'errors' => $errores
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}


/**
 * ============================================================
 * ARMAR PAYLOAD FLOWABLE
 * ============================================================
 */

$payload = [
    'processDefinitionKey' => $FLOWABLE_PROCESS_DEFINITION_KEY,
    'variables' => [
        [
            'name' => 'fecha',
            'value' => date('Y-m-d\TH:i:s')
        ],
        [
            'name' => 'rut',
            'value' => $rut
        ],
        [
            'name' => 'nombre',
            'value' => $nombre
        ],
        [
            'name' => 'monto',
            'value' => $monto
        ],
        [
            'name' => 'diaMes',
            'value' => $diaMes
        ]
    ]
];


/**
 * ============================================================
 * LLAMADA A FLOWABLE
 * ============================================================
 */

$url = rtrim($FLOWABLE_BASE_URL, '/') . $FLOWABLE_PROCESS_ENDPOINT;

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $FLOWABLE_USERNAME . ':' . $FLOWABLE_PASSWORD,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => $CURL_TIMEOUT
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);


/**
 * ============================================================
 * MANEJO DE ERRORES CURL
 * ============================================================
 */

if ($error) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Error CURL al conectar con Flowable',
        'error' => $error
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}


/**
 * ============================================================
 * RESPUESTA FLOWABLE
 * ============================================================
 */

$responseData = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300) {

    echo json_encode([
        'success' => true,
        'message' => 'Proceso iniciado correctamente',
        'flowableResponse' => $responseData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}


/**
 * ============================================================
 * ERROR FLOWABLE
 * ============================================================
 */

http_response_code($httpCode ?: 500);

echo json_encode([
    'success' => false,
    'message' => 'Flowable respondió con error',
    'statusCode' => $httpCode,
    'flowableResponse' => $responseData,
    'rawResponse' => $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);