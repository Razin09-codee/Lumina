<?php
// search-engine.php

// Enable CORS so your GitHub Pages frontend can access this backend securely
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json');

// Handle preflight browser security checks gracefully
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Read regular POST variables, or fallback to raw JSON input payload if needed
$userMessage = $_POST['message'] ?? '';
if (empty($userMessage)) {
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $userMessage = $rawInput['message'] ?? '';
}

if (empty($userMessage)) {
    echo json_encode(["reply" => "Empty message array packet."]);
    exit;
}

// Fetch the Gemini API Key safely stored in the environment variables configuration panel
$apiKey = getenv('GEMINI_API_KEY'); 
$modelName = "gemini-2.5-flash";

if (!$apiKey) {
    echo json_encode(["reply" => "Error: Engine key environment context variable missing."]);
    exit;
}

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent?key=" . $apiKey;

// ROUTE 1: AI Dynamic Search (Generates HTML Webpages)
if (strpos($userMessage, 'Generate a Top 10 webpage response array for:') !== false) {
    $topic = str_replace('Generate a Top 10 webpage response array for:', '', $userMessage);
    
    $systemPrompt = "You are a professional frontend web generator for the platform WEBREX. 
    The user searched for the topic: '$topic'. Generate a beautiful 'Top 10' layout page in raw HTML/CSS.
    Use an animated linear gradient background layout, modern flex/grid item cards, and smooth hover translations.
    Output ONLY functional raw code starting with <!DOCTYPE html>. No markdown ticks.";

    $postData = [
        "contents" => [["parts" => [["text" => $systemPrompt . "\n\nBuild layout now."]]]]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $generatedHTML = $result['candidates'][0]['content']['parts'][0]['text'];
        // Clean up markdown ticks if the AI accidentally generates them anyway
        $generatedHTML = preg_replace('/^```html\s*|```\s*$/i', '', trim($generatedHTML));
        echo json_encode(["html_payload" => $generatedHTML]);
    } else {
        echo json_encode(["reply" => "Error: AI engine failed to build the layout payload structure."]);
    }
    exit;
}

// ROUTE 2: General Chatbot Conversational Route
// This now securely talks to Gemini instead of returning a hardcoded sentence!
$postData = [
    "contents" => [["parts" => [["text" => "You are the WEBREX AI Assistant. Keep answers engaging and concise. User says: " . $userMessage]]]]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $replyText = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(["reply" => trim($replyText)]);
} else {
    echo json_encode(["reply" => "Portal connection open, but engine could not parse a textual chat response."]);
}
exit;