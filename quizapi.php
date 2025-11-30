<?php
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Riceve i dati dallo studente
    $data = json_decode(file_get_contents('php://input'), true);
    $filename = './topics/' . $data['topic'] . '/results.json';
    if ($data) {
        $currentData = [];
        if (file_exists($filename)) {
            $currentData = json_decode(file_get_contents($filename), true);
        }
        $currentData[] = $data;
        file_put_contents($filename, json_encode($currentData, JSON_PRETTY_PRINT));
    }
    echo json_encode(["status" => "success"]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Restituisce i dati per il professore
    if (file_exists($filename)) {
        echo file_get_contents($filename);
    } else {
        echo json_encode([]);
    }
    exit;
}
?>
