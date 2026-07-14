<?php
// 1. ALLOW EXTERNAL REQUESTS FROM YOUR GITHUB PAGES FRONTEND
header("Access-Control-Allow-Origin: https://razin09-codee.github.io");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// 2. HANDLE PREFLIGHT BROWSER CHECKS (OPTIONS METHOD)
// Browsers send an OPTIONS request first to see if the server allows POST. We must say YES instantly.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 3. CAPTURE INPUT PAYLOAD Safely
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    // If standard POST is empty, check raw JSON input payload format as a fallback
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);
    if (!empty($jsonData['message'])) {
        $message = trim($jsonData['message']);
    }
}

if (empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(["reply" => "Empty message array packet."]);
    exit;
}

// 4. FETCH KEY FROM VERCEL
$apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? null);
if (!$apiKey) {
    header('Content-Type: application/json');
    echo json_encode(["reply" => "Error: Secure API key configuration is missing on Vercel backend."]);
    exit;
}

// 5. CONNECT TO DYNAMIC AI NODE
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $message]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');

if ($httpCode !== 200 || !$response) {
    echo json_encode(["reply" => "Failed to reach the AI processor node. Status code: " . $httpCode]);
    exit;
}

$responseData = json_decode($response, true);
$botReply = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? "Unable to extract text structure from remote node payload.";

// 6. OUTPUT VALID JSON ARRAY PACKET
if (strpos($message, 'Generate a Top 10 webpage response array for:') !== false) {
    echo json_encode([
        "html_payload" => $botReply,
        "reply" => "Dynamic page generation verified."
    ]);
} else {
    echo json_encode(["reply" => $botReply]);
}
?>