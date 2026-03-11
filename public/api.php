<?php
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$apiKey = getenv("DEEPSEEK_API_KEY");
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Missing DEEPSEEK_API_KEY"]);
    exit;
}

$raw = file_get_contents("php://input");
$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload["message"])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request body"]);
    exit;
}

$message = trim($payload["message"]);
if ($message === "") {
    http_response_code(400);
    echo json_encode(["error" => "Message is required"]);
    exit;
}

$requestBody = [
    "model" => "deepseek-chat",
    "messages" => [
        ["role" => "user", "content" => $message]
    ],
    "stream" => false
];

$ch = curl_init("https://api.deepseek.com/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(502);
    echo json_encode(["error" => "Upstream request failed"]);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($status < 200 || $status >= 300) {
    http_response_code(502);
    $msg = is_array($data) && isset($data["error"]["message"]) ? $data["error"]["message"] : "Upstream error";
    echo json_encode(["error" => $msg]);
    exit;
}

$reply = "";
if (is_array($data) && isset($data["choices"][0]["message"]["content"])) {
    $reply = $data["choices"][0]["message"]["content"];
}

echo json_encode(["reply" => $reply]);
