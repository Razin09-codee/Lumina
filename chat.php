<?php
header('Content-Type: application/json');

$userMessage = $_POST['message'] ?? '';
if (!$userMessage) {
    echo json_encode(['reply' => 'No message received']);
    exit;
}

$apiKey = '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'; // Replace with your real key
$url = 'https://openrouter.ai/api/v1/chat/completions';

$data = [
    "model" => "openai/gpt-3.5-turbo", // You can try other models too
    "messages" => [
        ["role" => "user", "content" => $userMessage]
    ]
];

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\nHTTP-Referer: your-site.com\r\n",
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === FALSE) {
    echo json_encode(['reply' => '⚠️ Error connecting to AI server']);
    exit;
}

$result = json_decode($response, true);
$aiReply = $result['choices'][0]['message']['content'] ?? 'No response from AI';
echo json_encode(['reply' => $aiReply]);
