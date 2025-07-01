<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = isset($_POST['estado']) ? $_POST['estado'] : null;
    $conexion = isset($_POST['conexion']) ? $_POST['conexion'] : null;

    $datosActualizar = [];

    if ($conexion) {
        $datosActualizar['conexion'] = $conexion;
    }

    if ($estado) {
        $datosActualizar['estado'] = $estado;
    }

    if (empty($datosActualizar)) {
        http_response_code(400);
        echo json_encode(["error" => "No se enviaron datos válidos"]);
        exit;
    }

    $logEntry = date('Y-m-d H:i:s') . " - Datos recibidos: " . json_encode($datosActualizar) . "\n";
    file_put_contents('log.txt', $logEntry, FILE_APPEND);

    $firebase_url = 'https://sahur-server-default-rtdb.firebaseio.com/.json';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $firebase_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosActualizar));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
    ));

    $resultado = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        echo json_encode([
            "success" => true,
            "datos_actualizados" => $datosActualizar,
            "mensaje_log" => "Datos guardados en log.txt"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => "Error al actualizar Firebase",
            "detalle" => $resultado
        ]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Mostrar mensaje y últimas 5 líneas del log
    $logContent = @file_get_contents('log.txt');
    $ultimasLineas = [];
    if ($logContent) {
        $lineas = explode("\n", trim($logContent));
        $ultimasLineas = array_slice($lineas, -5);
    }
    echo json_encode([
        "mensaje" => "Servidor activo. Enviar datos con método POST.",
        "ultima_entrada_log" => $ultimasLineas
    ]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}
?>
