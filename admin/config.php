<?php
// Configuración de la Fundación
session_start();
define('AUTH_USER', 'admin');
define('AUTH_PASS', 'Fundacion26'); // Credenciales hardcodeadas para el ejemplo

// Configuración Flowable (Ajustar según tu instalación)
define('FLOWABLE_API', 'http://localhost:7070/flowable-rest/service');
define('FLOWABLE_USER', 'rest-admin'); 
define('FLOWABLE_PASS', 'test');

function flowable_request($endpoint, $method = 'GET', $data = null) {
    $ch = curl_init(FLOWABLE_API . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, FLOWABLE_USER . ":" . FLOWABLE_PASS);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Control de acceso ultra simple
function check_auth() {
    if (!isset($_SESSION['logged'])) {
        header("Location: index.php");
        exit;
    }
}
