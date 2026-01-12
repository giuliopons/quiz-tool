<?php
header('Content-Type: application/json');

include("config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$topicRaw = isset($input['topic']) ? trim($input['topic']) : '';

if ($topicRaw === '') {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Missing topic']);
    exit;
}

$topicSlug = preg_replace('/[^a-z0-9_-]+/i', '-', $topicRaw);
$topicSlug = trim($topicSlug, '-');

if ($topicSlug === '') {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid topic']);
    exit;
}

$topicsDir = __DIR__ . '/topics';
$topicDir = $topicsDir . '/' . $topicSlug;

if (file_exists($topicDir)) {
    http_response_code(409);
    echo json_encode(['status' => 'fail', 'message' => 'Topic already exists', 'topic' => $topicSlug]);
    exit;
}

if (!$LLM_API_KEY) {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'message' => 'Missing OPENAI_API_KEY']);
    exit;
}

function callOpenAI(array $messages, float $temperature): array
{
    global $LLM_API_KEY;

    $request = [
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => $temperature,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $LLM_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        http_response_code(502);
        echo json_encode(['status' => 'fail', 'message' => 'OpenAI request failed: ' . $error]);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        http_response_code(502);
        echo json_encode(['status' => 'fail', 'message' => 'OpenAI returned HTTP ' . $httpCode]);
        exit;
    }

    $responseData = json_decode($response, true);
    $content = $responseData['choices'][0]['message']['content'] ?? '';

    if ($content === '') {
        http_response_code(502);
        echo json_encode(['status' => 'fail', 'message' => 'Empty OpenAI response']);
        exit;
    }

    return ['content' => $content];
}

$prompt = 'Create a true/false quiz in Italian about: "' . $topicRaw . '". '
    . 'Return ONLY a JSON array of objects, each with keys "question" (string) and "answer" (boolean). '
    . 'Provide 15 to 25 items. No markdown, no extra text.';

$genResponse = callOpenAI(
    [
        ['role' => 'system', 'content' => 'You generate quiz data as JSON.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    0.7
);

$content = $genResponse['content'];

$quizData = json_decode($content, true);
if (!is_array($quizData)) {
    $start = strpos($content, '[');
    $end = strrpos($content, ']');
    if ($start !== false && $end !== false && $end > $start) {
        $jsonSlice = substr($content, $start, $end - $start + 1);
        $quizData = json_decode($jsonSlice, true);
    }
}

if (!is_array($quizData)) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid JSON from OpenAI']);
    exit;
}

$normalized = [];
foreach ($quizData as $item) {
    if (!is_array($item)) {
        continue;
    }
    $question = isset($item['question']) ? trim((string)$item['question']) : '';
    $answer = $item['answer'] ?? null;

    if ($question === '') {
        continue;
    }

    if (is_string($answer)) {
        if (strcasecmp($answer, 'true') === 0) {
            $answer = true;
        } elseif (strcasecmp($answer, 'false') === 0) {
            $answer = false;
        }
    }

    if (!is_bool($answer)) {
        continue;
    }

    $normalized[] = ['question' => $question, 'answer' => $answer];
}

if (count($normalized) < 5) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Not enough valid quiz items']);
    exit;
}

$verificationPrompt = 'Verify and correct this Italian true/false quiz about "' . $topicRaw . '". '
    . 'Check each statement for factual accuracy. If a statement is wrong, fix the statement or flip the answer so it becomes correct. '
    . 'Remove ambiguous or unverifiable items; if you remove any, replace them with new verified items. '
    . 'Return ONLY a JSON array of objects with "question" (string) and "answer" (boolean). No markdown. '
    . 'Quiz JSON: ' . json_encode($normalized, JSON_UNESCAPED_UNICODE);

$verifyResponse = callOpenAI(
    [
        ['role' => 'system', 'content' => 'You are a strict fact-checker for quiz statements.'],
        ['role' => 'user', 'content' => $verificationPrompt],
    ],
    0.2
);

$verifiedContent = $verifyResponse['content'];
$verifiedData = json_decode($verifiedContent, true);
if (!is_array($verifiedData)) {
    $start = strpos($verifiedContent, '[');
    $end = strrpos($verifiedContent, ']');
    if ($start !== false && $end !== false && $end > $start) {
        $jsonSlice = substr($verifiedContent, $start, $end - $start + 1);
        $verifiedData = json_decode($jsonSlice, true);
    }
}

if (!is_array($verifiedData)) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid verification JSON from OpenAI']);
    exit;
}

$verifiedNormalized = [];
foreach ($verifiedData as $item) {
    if (!is_array($item)) {
        continue;
    }
    $question = isset($item['question']) ? trim((string)$item['question']) : '';
    $answer = $item['answer'] ?? null;

    if ($question === '') {
        continue;
    }

    if (is_string($answer)) {
        if (strcasecmp($answer, 'true') === 0) {
            $answer = true;
        } elseif (strcasecmp($answer, 'false') === 0) {
            $answer = false;
        }
    }

    if (!is_bool($answer)) {
        continue;
    }

    $verifiedNormalized[] = ['question' => $question, 'answer' => $answer];
}

if (count($verifiedNormalized) < 5) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Not enough verified quiz items']);
    exit;
}

if (!is_dir($topicsDir)) {
    mkdir($topicsDir, 0775, true);
}

if (!mkdir($topicDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'message' => 'Failed to create topic directory']);
    exit;
}

file_put_contents($topicDir . '/quizdata.json', json_encode($verifiedNormalized, JSON_PRETTY_PRINT));
file_put_contents($topicDir . '/results.json', json_encode([]));

echo json_encode(['status' => 'success', 'topic' => $topicSlug]);
