<?php
// 1. Clear the CORS policy blocks for your specific frontend domain
header("Access-Control-Allow-Origin: https://razin09-codee.github.io");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight browser check packets cleanly
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 2. Safely capture the user input payload
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(["reply" => "Empty message array packet."]);
    exit;
}

// 3. Extract the hidden API key from the Render dashboard configuration
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    header('Content-Type: application/json');
    echo json_encode(["reply" => "Error: Secure API key configuration is missing on backend."]);
    exit;
}

// 4. Configure the official Gemini API request endpoint
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

// 5. Execute a standard secure cURL request pipeline
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

// 6. Check if frontend requested a dynamic layout presentation matrix
if (strpos($message, 'Generate a Top 10 webpage response array for:') !== false) {
    echo json_encode([
        "html_payload" => $botReply,
        "reply" => "Dynamic page generation verified."
    ]);
} else {
    echo json_encode(["reply" => $botReply]);
}
?>