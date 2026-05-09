<?php
require 'config.php';
check_auth();

$taskId = $_GET['id'] ?? null;
if (!$taskId) header("Location: index.php");

// 1. Obtener variables de la tarea para mostrar al administrador
$variablesRaw = flowable_request("/runtime/tasks/$taskId/variables");
$vars = [];
foreach ($variablesRaw as $v) { $vars[$v['name']] = $v['value']; }

// 2. Procesar la decisión (Aprobar o Rechazar)
if (isset($_POST['decidir'])) {
    $decision = ($_POST['accion'] === 'aprobar');
    
    // Completar la tarea enviando la variable que espera el Exclusive Gateway
    $payload = [
        "action" => "complete",
        "variables" => [
            ["name" => "esValido", "value" => $decision, "type" => "boolean"]
        ]
    ];
    
    flowable_request("/runtime/tasks/$taskId", "POST", $payload);
    header("Location: index.php?msg=ok");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Evaluar Postulante</title>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-8 shadow-md rounded-xl">
        <h2 class="text-2xl font-bold mb-6 text-indigo-700 underline">Detalle de Postulación</h2>
        
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-xs text-gray-500 uppercase">Nombre Postulante</p>
                <p class="font-semibold text-lg"><?= $vars['nombre'] ?? 'N/A' ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-xs text-gray-500 uppercase">RUT</p>
                <p class="font-semibold text-lg"><?= $vars['rut'] ?? 'N/A' ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-xs text-gray-500 uppercase">Monto Mensual</p>
                <p class="font-semibold text-lg text-green-600">$<?= number_format($vars['monto'] ?? 0, 0, ',', '.') ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-xs text-gray-500 uppercase">Día de Cobro</p>
                <p class="font-semibold text-lg">Los <?= $vars['diaMes'] ?? 'N/A' ?> de cada mes</p>
            </div>
        </div>

        <form method="POST" class="border-t pt-6 flex justify-between">
            <button name="decidir" value="1" onclick="document.getElementsByName('accion')[0].value='rechazar'" 
                class="bg-red-100 text-red-700 px-6 py-3 rounded-lg font-bold hover:bg-red-200">
                RECHAZAR POSTULACIÓN
            </button>
            
            <button name="decidir" value="1" onclick="document.getElementsByName('accion')[0].value='aprobar'" 
                class="bg-green-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-700">
                APROBAR SOCIO
            </button>
            
            <input type="hidden" name="accion" value="">
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-gray-400 text-sm hover:underline">Volver atrás</a>
        </div>
    </div>
</body>
</html>