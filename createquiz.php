<?php
/**
 * Endpoint for creating new quizzes using an LLM.
 * 
 */
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

function isListArray(array $value): bool
{
    return array_keys($value) === range(0, count($value) - 1);
}

function decodeQuizContent(string $content): ?array
{
    $data = json_decode($content, true);
    if (!is_array($data)) {
        $start = strpos($content, '[');
        $end = strrpos($content, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $jsonSlice = substr($content, $start, $end - $start + 1);
            $data = json_decode($jsonSlice, true);
        } else {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $jsonSlice = substr($content, $start, $end - $start + 1);
                $data = json_decode($jsonSlice, true);
            }
        }
    }

    if (!is_array($data)) {
        return null;
    }

    $title = '';
    $items = [];
    $count = null;

    if (isListArray($data)) {
        $items = $data;
    } else {
        if (isset($data['titolo']) && is_string($data['titolo'])) {
            $title = trim($data['titolo']);
        } elseif (isset($data['title']) && is_string($data['title'])) {
            $title = trim($data['title']);
        }

        if (isset($data['numero_domande']) && is_numeric($data['numero_domande'])) {
            $count = (int)$data['numero_domande'];
        } elseif (isset($data['question_count']) && is_numeric($data['question_count'])) {
            $count = (int)$data['question_count'];
        }

        if (isset($data['domande']) && is_array($data['domande'])) {
            $items = $data['domande'];
        } elseif (isset($data['questions']) && is_array($data['questions'])) {
            $items = $data['questions'];
        }
    }

    return ['title' => $title, 'items' => $items, 'count' => $count];
}

function normalizeQuizItems(array $items): array
{
    $normalized = [];
    foreach ($items as $item) {
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

    return $normalized;
}

function needsBalance(array $items, int $minEach): bool
{
    $trueCount = 0;
    $falseCount = 0;
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (!isset($item['answer'])) {
            continue;
        }
        if ($item['answer'] === true) {
            $trueCount++;
        } elseif ($item['answer'] === false) {
            $falseCount++;
        }
    }

    return $trueCount < $minEach || $falseCount < $minEach;
}

$prompt = 'Create a true/false quiz in Italian about: "' . $topicRaw . '". '
    . 'If the topic text includes a desired number of questions, use that exact number; otherwise generate exactly 10 items. '
    . 'Return ONLY a JSON object with keys "titolo" (string), "domande" (array), and "numero_domande" (integer). '
    . 'Each item in "domande" must have keys "question" (string) and "answer" (boolean). '
    . 'Ensure a balanced mix of true and false answers (at least 30% of each). '
    . 'No markdown, no extra text.';

$genResponse = callOpenAI(
    [
        ['role' => 'system', 'content' => 'You generate quiz data as JSON.'],
        ['role' => 'user', 'content' => $prompt],
    ],
    0.7
);

$content = $genResponse['content'];
$decoded = decodeQuizContent($content);
if ($decoded === null) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid JSON from OpenAI']);
    exit;
}

$normalized = normalizeQuizItems($decoded['items']);
$expectedCount = (isset($decoded['count']) && $decoded['count'] > 0)
    ? (int)$decoded['count']
    : (count($normalized) > 0 ? count($normalized) : 10);
$generatedTitle = $decoded['title'] !== '' ? $decoded['title'] : $topicRaw;

if (count($normalized) < 5) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Not enough valid quiz items']);
    exit;
}

$verificationPrompt = 'Verify and correct this Italian true/false quiz about "' . $topicRaw . '". '
    . 'Check each statement for factual accuracy. If a statement is wrong, fix the statement or flip the answer so it becomes correct. '
    . 'Remove ambiguous or unverifiable items; if you remove any, replace them with new verified items. '
    . 'Preserve the requested number of questions if specified in the topic; otherwise keep exactly 10 items. '
    . 'Ensure exactly ' . $expectedCount . ' items total. '
    . 'Return ONLY a JSON object with "titolo" (string), "domande" (array), and "numero_domande" (integer). '
    . 'Each item in "domande" must have "question" (string) and "answer" (boolean). '
    . 'Ensure a balanced mix of true and false answers (at least 30% of each). '
    . 'No markdown. Quiz JSON: ' . json_encode(['titolo' => $generatedTitle, 'domande' => $normalized], JSON_UNESCAPED_UNICODE);

$verifyResponse = callOpenAI(
    [
        ['role' => 'system', 'content' => 'You are a strict fact-checker for quiz statements.'],
        ['role' => 'user', 'content' => $verificationPrompt],
    ],
    0.2
);

$verifiedContent = $verifyResponse['content'];
$verifiedDecoded = decodeQuizContent($verifiedContent);
if ($verifiedDecoded === null) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid verification JSON from OpenAI']);
    exit;
}

$verifiedNormalized = normalizeQuizItems($verifiedDecoded['items']);
$expectedCount = (isset($verifiedDecoded['count']) && $verifiedDecoded['count'] > 0)
    ? (int)$verifiedDecoded['count']
    : (count($verifiedNormalized) > 0 ? count($verifiedNormalized) : $expectedCount);
$verifiedTitle = $verifiedDecoded['title'] !== '' ? $verifiedDecoded['title'] : $generatedTitle;

if (count($verifiedNormalized) < 5) {
    http_response_code(502);
    echo json_encode(['status' => 'fail', 'message' => 'Not enough verified quiz items']);
    exit;
}

$minEach = max(1, (int)floor($expectedCount * 0.3));
if (needsBalance($verifiedNormalized, $minEach)) {
    $balancePrompt = 'Rebalance the following Italian true/false quiz about "' . $topicRaw . '". '
        . 'Keep factual accuracy and preserve the requested number of questions if specified in the topic; otherwise keep exactly 10 items. '
        . 'You may rewrite statements or flip answers to make them correct, and replace items if needed. '
        . 'Ensure exactly ' . $expectedCount . ' items total. '
        . 'Return ONLY a JSON object with "titolo" (string), "domande" (array), and "numero_domande" (integer). '
        . 'Each item in "domande" must have "question" (string) and "answer" (boolean). '
        . 'No markdown. Quiz JSON: ' . json_encode(['titolo' => $verifiedTitle, 'domande' => $verifiedNormalized], JSON_UNESCAPED_UNICODE);

    $balanceResponse = callOpenAI(
        [
            ['role' => 'system', 'content' => 'You balance true/false quiz answers while staying accurate.'],
            ['role' => 'user', 'content' => $balancePrompt],
        ],
        0.2
    );

    $balancedDecoded = decodeQuizContent($balanceResponse['content']);
    if ($balancedDecoded !== null) {
        $balancedNormalized = normalizeQuizItems($balancedDecoded['items']);
        if (isset($balancedDecoded['count']) && $balancedDecoded['count'] > 0) {
            $expectedCount = (int)$balancedDecoded['count'];
        } elseif (count($balancedNormalized) > 0) {
            $expectedCount = count($balancedNormalized);
        }
        $balancedTitle = $balancedDecoded['title'] !== '' ? $balancedDecoded['title'] : $verifiedTitle;
        if (count($balancedNormalized) >= 5 && !needsBalance($balancedNormalized, $minEach)) {
            $verifiedNormalized = $balancedNormalized;
            $verifiedTitle = $balancedTitle;
        }
    }
}

if (!is_dir($topicsDir)) {
    mkdir($topicsDir, 0775, true);
}

if (!mkdir($topicDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'message' => 'Failed to create topic directory']);
    exit;
}

file_put_contents($topicDir . '/quizdata.json', json_encode(['titolo' => $verifiedTitle, 'domande' => $verifiedNormalized], JSON_PRETTY_PRINT));
file_put_contents($topicDir . '/results.json', json_encode([]));

echo json_encode(['status' => 'success', 'topic' => $topicSlug]);
