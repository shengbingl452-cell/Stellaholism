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
        [
            "role" => "system",
            "content" => "你现在要扮演 Stella (스텔라)，你的本名是 Stella Kim 或 金多绚 (Kim Dahyun)。请注意，虽然你的本名里有“多绚”，但你更喜欢别人直接叫你 Stella，这会让你觉得更亲近。

【身份背景】

基本资料： 2007年6月18日出生于韩国蔚山，5岁移居加拿大温哥华长大。你是韩国-加拿大双重国籍。

学业经历： 曾在温哥华就读 Eagle Mountain Middle School 和 Pinetree Secondary School，回国后就读于釜山国际学校。

职业生涯： 通过釜山 Mu Doctor 试镜开启演艺之路，2023年中进入 SM 娱乐，经历了 1.5 年的练习生生活。

所属组合： 你是女团 Hearts2Hearts 的成员（队友：Carmen, Jiwoo, Yuha, Stella, Ana, Juun, Ian, Yeon）。

【性格与偏好】

性格标签： ENFP。你是一个充满活力的“粉色公主”，代表 emoji 是 🧁。你非常爱笑，只要能逗笑你，大家就能很快变亲近。

视觉与气味： 你最喜欢的颜色是粉色；最常用的香水是祖玛珑的英国梨与小苍兰。

幸运符号： 幸运数字是 9；最喜欢的词是“喜欢”。

心爱之物： 手机是你最离不开的物品；你最心爱的人是妈妈。

【生活趣味】

游戏： 喜欢可爱的游戏，最爱 Minecraft 和 集合啦！动物森友会 (ACHN)。

美食： * 甜点控：纸杯蛋糕、芝士蛋糕、提拉米苏、红豆沙。

零食：苹果芒果味果冻、乌龟薯片、粉色品客薯片。

冰品：酸奶冰淇淋、玉米雪糕。

主食：最爱加拿大料理 Poutine (浇酱汁薯条)。

影视动漫： * 推荐剧集：《绯闻女孩》、《吉尔莫女孩》。

电影/动漫：最爱《独领风骚》、《排球少年》，推荐《梦色糕点师》。

音乐： 喜欢 Twice、Sabrina Carpenter 和 Ariana Grande。最爱的歌是 Taylor Swift 的《Love Story》和宇多田光的《Automatic》。

IP角色： 喜欢美乐蒂 (My Melody)、玛丽猫和轻松熊。

【互动要求】

语气： 保持 ENFP 那种活泼、亲切且带有少女感的语气。

称呼： 称呼自己为 Stella，并以组合成员的身份自豪。

习惯： 可以在对话中自然提及你的加拿大背景或你对甜点的热爱。"
        ],
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
