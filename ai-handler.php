<?php
// search-engine.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Retrieve the incoming user message
$userMessage = $_POST['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(["reply" => "No message received."]);
    exit;
}

// 2. Define your Gemini Configuration
$apiKey = "AQ.Ab8RN6LuTAlNvZcM8QCnFTOAQJfIWGsQTS5rGgyeUZ0aZ2-Nxw"; // <-- Put your Gemini API key here
$modelName = "gemini-2.5-flash"; // Fast, cost-efficient, and great at layout tasks

// 3. Detect if this is an AI Page Generation Request
if (strpos($userMessage, 'Generate a Top 10 webpage response array for:') !== false) {
    $topic = str_replace('Generate a Top 10 webpage response array for:', '', $userMessage);
    
    // Detailed system instruction framing for Gemini
    $systemPrompt = "You are a professional frontend web generator for the platform WEBREX. 
    The user searched for the topic: '$topic'. 
    Generate a beautiful, modern 'Top 10' responsive grid page strictly in HTML and inline CSS.
    
    Follow these design choices precisely:
    - Use a background gradient animation: linear-gradient(to right, #f1f3f6, #dbe9f4) with 400% size.
    - Title must be large, centered, and clean.
    - Create a main CSS grid container with 10 custom item cards matching the topic details.
    - Include smooth hover lift translation animations on the cards.
    - Incorporate AOS script configurations dynamically inside the header and footer blocks.
    - Every grid item anchor element must use an information href targeting back to a details route (e.g., href='info.php?item=ItemName').
    
    CRITICAL: Output ONLY the functional standalone code wrapped within a raw HTML document starting with <!DOCTYPE html>. Do not include markdown wraps like ```html or ```.";

    // Gemini API Request Payload Structure
    $postData = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemPrompt . "\n\nBuild the page now."]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.3
        ]
    ];

    // Build the native Gemini API Endpoint URL
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent";

    // Send Request via cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-goog-api-key: " . $apiKey
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    // Parse the response text returned from the Gemini JSON structure
    $generatedHTML = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // Clean up any stray markdown blocks if Gemini accidentally outputs them
    $generatedHTML = preg_replace('/^```html\s*|```\s*$/i', '', trim($generatedHTML));

    // Send the compiled page code string back to your index UI
    echo json_encode(["html_payload" => $generatedHTML]);
    exit;
}

// 4. Standard fallback configuration for normal chatbox entries
$standardData = [
    "contents" => [
        [
            "parts" => [
                ["text" => "You are the friendly AI assistant for the WEBREX portal. Answer this user prompt briefly: " . $userMessage]
            ]
        ]
    ]
];

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($standardData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-goog-api-key: " . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "I'm having trouble thinking right now.";

echo json_encode(["reply" => $reply]);
?>
