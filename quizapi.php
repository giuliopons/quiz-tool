<?php
/**
 * API endpoint for saving quiz results and fetching them for the ranking page.
 * - POST: Save quiz results sent by the student.
 * - GET: Fetch quiz results for the professor's ranking page.
 */


header('Content-Type: application/json');

include("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Riceve i dati dallo studente
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['name']) && isset($data['score']) && isset($data['correctAnswers']) && isset($data['topic'])) {
        $topic = trim(normalizeQuizTopicSlug($data['topic']), '-');
        if (!isQuizTopicAllowed($topic, './topics')) {
            http_response_code(400);
            echo json_encode(["status" => "fail", "message" => "Invalid topic"]);
            exit;
        }

        $filename = './topics/' . $topic . '/results.json';
        $currentData = [];
        if (file_exists($filename)) {
            $currentData = json_decode(file_get_contents($filename), true);
        }
        if (!is_array($currentData)) {
            $currentData = [];
        }

        $data['topic'] = $topic;
        $data['datetime'] = date('Y-m-d H:i:s');
        $currentData[] = $data;
        file_put_contents($filename, json_encode($currentData, JSON_PRETTY_PRINT));
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "fail"]);
    }
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