<?php
require 'config.php';

// 1. Funciones de utilidad
function get_variable_value($variables, $name) {
    if (!is_array($variables)) return 'N/A';
    foreach ($variables as $var) {
        if (isset($var['name']) && $var['name'] === $name) {
            return $var['value'];
        }
    }
    return 'N/A';
}

/**
 * Busca el nombre del nodo BPMN donde está el "token" actualmente.
 * Utiliza el endpoint de actividades históricas no finalizadas.
 */
function get_current_step_name($instanceId) {
    // Consulta actividades no terminadas para esta instancia
    $path = "/history/historic-activity-instances?processInstanceId=$instanceId&finished=false";
    $response = flowable_request($path);
    
    if (!empty($response['data'])) {
        // Extraemos los nombres de las actividades activas
        $names = array_map(function($act) {
            return $act['activityName'] ?? 'Tarea sin nombre';
        }, $response['data']);
        
        return implode(', ', $names);
    }
    return 'Finalizando...';
}

// 2. Control de Login
if (isset($_POST['login'])) {
    if ($_POST['user'] === AUTH_USER && $_POST['pass'] === AUTH_PASS) {
        $_SESSION['logged'] = true;
    }
}

if (isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: index.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fundación Animalitos - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">

    <?php if (!isset($_SESSION['logged'])): ?>
        <div class="flex items-center justify-center h-screen">
            <form method="POST" class="p-10 bg-white shadow-2xl rounded-3xl w-96 border border-slate-100">
                <h2 class="text-3xl font-black mb-8 text-center text-indigo-600 tracking-tighter">ADMIN LOGIN</h2>
                <div class="space-y-5">
                    <input type="text" name="user" placeholder="Usuario" class="w-full p-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    <input type="password" name="pass" placeholder="Contraseña" class="w-full p-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    <button name="login" class="w-full bg-indigo-600 text-white p-4 rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">ENTRAR</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <nav class="bg-white border-b border-slate-200 p-4 sticky top-0 z-10">
            <div class="container mx-auto flex justify-between items-center">
                <span class="font-black text-indigo-600 text-xl tracking-tighter">FUNDACIÓN ANIMALITOS</span>
                <a href="?logout=1" class="text-xs font-bold text-slate-400 hover:text-red-500 transition-colors uppercase tracking-widest">Cerrar Sesión</a>
            </div>
        </nav>

        <div class="container mx-auto p-8 space-y-12">
            
            <section>
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                    Tareas Pendientes de Evaluación
                </h2>
                <div class="bg-white shadow-sm rounded-3xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 text-[10px] font-bold uppercase">
                            <tr>
                                <th class="p-5">ID Tarea</th>
                                <th class="p-5">Acción Requerida</th>
                                <th class="p-5">Fecha de Ingreso</th>
                                <th class="p-5 text-right">Operación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            $tasks = flowable_request('/runtime/tasks?processDefinitionKey=fundacionInscripcionSocio');
                            if (!empty($tasks['data'])):
                                foreach ($tasks['data'] as $t): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-5 text-xs font-mono text-slate-300"><?= $t['id'] ?></td>
                                    <td class="p-5 font-bold text-slate-700"><?= $t['name'] ?></td>
                                    <td class="p-5 text-sm text-slate-400"><?= date('d/m/y H:i', strtotime($t['createTime'])) ?></td>
                                    <td class="p-5 text-right">
                                        <a href="/admin/evaluar.php?id=<?= $t['id'] ?>" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition-all">GESTIONAR</a>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="p-10 text-center text-slate-300 italic text-sm">No hay evaluaciones pendientes en este momento.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2"></span>
                    Monitor de Pasos Actuales
                </h2>
                <div class="bg-white shadow-sm rounded-3xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 text-[10px] font-bold uppercase">
                            <tr>
                                <th class="p-5">Fecha Inicio</th>
                                <th class="p-5">Nombre Postulante</th>
                                <th class="p-5">Paso Actual (Nodo BPMN)</th>
                                <th class="p-5 text-center">Estado Motor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            $histPath = '/history/historic-process-instances?processDefinitionKey=fundacionInscripcionSocio&size=10&includeProcessVariables=true&sort=startTime&order=desc';
                            $history = flowable_request($histPath);

                            if (!empty($history['data'])):
                                foreach ($history['data'] as $proc): 
                                    $nombreSocio = get_variable_value($proc['variables'] ?? [], 'nombre');
                                    $terminado = !empty($proc['endTime']);
                                    
                                    // Dinámicamente obtenemos el nombre del paso actual si está activo
                                    $pasoActual = $terminado ? "Finalizado" : get_current_step_name($proc['id']);
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="p-5 text-sm text-slate-400"><?= date('d/m/y H:i', strtotime($proc['startTime'])) ?></td>
                                <td class="p-5 font-bold text-slate-700"><?= htmlspecialchars($nombreSocio) ?></td>
                                <td class="p-5">
                                    <div class="flex items-center">
                                        <?php if (!$terminado): ?>
                                            <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-ping"></div>
                                        <?php endif; ?>
                                        <span class="<?= $terminado ? 'text-slate-300' : 'text-indigo-600 font-bold' ?> text-sm uppercase">
                                            <?= $pasoActual ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <span class="text-[9px] font-black px-2 py-1 rounded-md <?= $terminado ? 'bg-slate-100 text-slate-400' : 'bg-emerald-100 text-emerald-600' ?>">
                                        <?= $terminado ? 'ARCHIVADO' : 'EJECUTANDO' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="p-10 text-center text-slate-300 italic text-sm">No se registran solicitudes históricas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    <?php endif; ?>
</body>
</html>