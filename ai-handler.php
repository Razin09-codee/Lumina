<?php
// chat.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Retrieve the incoming user message
$userMessage =$_POST['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(["reply" => "No message received."]);
    exit;
}

// 2. Define your AI Configuration
$apiKey = "YOUR_ACTUAL_API_KEY_HERE"; // <-- Put your API key here
$apiUrl = "https://api.openai.com/v1/chat/completions"; // Swap out if using Gemini/Anthropic APIs

// 3. Detect if this is an AI Page Generation Request
if (strpos($userMessage, 'Generate a Top 10 webpage response array for:') !== false) {
    $topic = str_replace('Generate a Top 10 webpage response array for:', '',$userMessage);
    
    // System message forcing the AI to output valid, raw HTML matching your grid template
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
    
    CRITICAL: Output ONLY the functional standalone code wrapped within a raw HTML document starting with <!DOCTYPE html>. Do not include markdown wraps like ```html.";

    $postData = [
        "model" => "gpt-4o-mini", // Or alternative preferred model target
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => "Build the page now."]
        ],
        "temperature" => 0.3
    ];

    // 4. Send Request via cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $generatedHTML = $result['choices'][0]['message']['content'] ?? '';

    // Send the compiled page code string back to your index UI
    echo json_encode(["html_payload" => $generatedHTML]);
    exit;
}

// 5. Standard fallback configuration for normal chatbox entries
$standardData = [
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => "You are the friendly AI assistant for the WEBREX portal."],
        ["role" => "user", "content" => $userMessage]
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($standardData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? "I'm having trouble thinking right now.";

echo json_encode(["reply" => $reply]);
?>