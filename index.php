<?php

// --- কনফিগারেশন শুরু ---
define('BOT_TOKEN', 'YOUR_BOT_TOKEN'); // আপনার বট টোকেন এখানে দিন
define('WEB_APP_URL', 'eid-special-lottery.vercel.app'); // আপনার ওয়েবসাইটের URL দিন
define('LOG_UPDATES', false); // true করলে সব ইনকামিং আপডেট error_log এ সেভ হবে (ডিবাগিং এর জন্য)

$required_channels = [
    [
        'id' => '@global_Fun22', // চ্যানেল ইউজারনেম
        'name' => 'Global Fun',
        'url' => 'https://t.me/global_Fun22'
    ],
    [
        'id' => '@instant_earn_airdrop',
        'name' => 'Instant Earn Airdrop',
        'url' => 'https://t.me/instant_earn_airdrop'
    ],
    [
        'id' => '@myearn_Cash_payment',
        'name' => 'MyEarn Cash Payment',
        'url' => 'https://t.me/myearn_Cash_payment'
    ]
];
// --- কনফিগারেশন শেষ ---


// --- টেলিগ্রাম API ফাংশন ---
functionapiRequest($method, $parameters = []) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = [];
    } elseif (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($parameters));
    // Render এর ফ্রি টায়ারে IPv6 সমস্যা হতে পারে, তাই IPv4 তে ফোর্স করা হচ্ছে
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl error ($errno): $error\n");
        curl_close($handle);
        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        // সার্ভার এরর হলে কিছুক্ষণ পর আবার চেষ্টা করার জন্য false রিটার্ন করা হতে পারে
        error_log("Telegram API error, HTTP code $http_code\n");
        return false;
    } elseif ($http_code != 200) {
        $response_decoded = json_decode($response, true);
        error_log("Request failed with code $http_code: " . $response_decoded['description'] . "\n");
        return false;
    }

    return json_decode($response, true);
}

function sendMessage($chat_id, $text, $reply_markup = null) {
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML' // Markdown বা HTML ব্যবহার করতে পারেন
    ];
    if ($reply_markup) {
        $params['reply_markup'] = json_encode($reply_markup);
    }
    return apiRequest('sendMessage', $params);
}

function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
    $params = [
        'callback_query_id' => $callback_query_id,
    ];
    if ($text) {
        $params['text'] = $text;
    }
    if ($show_alert) {
        $params['show_alert'] = true;
    }
    return apiRequest('answerCallbackQuery', $params);
}

function isUserMemberOfChannel($user_id, $channel_id) {
    $response = apiRequest('getChatMember', [
        'chat_id' => $channel_id,
        'user_id' => $user_id
    ]);

    if ($response && $response['ok']) {
        $status = $response['result']['status'];
        // 'creator', 'administrator', 'member' এগুলো জয়েনড হিসেবে গণ্য হবে
        return in_array($status, ['creator', 'administrator', 'member']);
    }
    // যদি কোনো কারণে API কল ফেইল হয় অথবা ব্যবহারকারী সদস্য না হয়
    error_log("getChatMember failed for user $user_id in channel $channel_id: " . print_r($response, true));
    return false;
}

// --- বট লজিক ---
function processUpdate($update) {
    global $required_channels;

    $message = $update['message'] ?? null;
    $callback_query = $update['callback_query'] ?? null;

    if ($message) {
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = $message['text'] ?? '';

        if ($text === '/start') {
            handleStartCommand($chat_id, $user_id);
        }
    } elseif ($callback_query) {
        $chat_id = $callback_query['message']['chat']['id'];
        $user_id = $callback_query['from']['id'];
        $data = $callback_query['data'];
        $callback_query_id = $callback_query['id'];

        if ($data === 'check_join_status') {
            answerCallbackQuery($callback_query_id); // বাটন প্রেস এর ফিডব্যাক
            handleStartCommand($chat_id, $user_id); // পুনরায় স্ট্যাটাস চেক
        }
    }
}

function handleStartCommand($chat_id, $user_id) {
    global $required_channels;
    $all_joined = true;
    $not_joined_channels_info = [];

    foreach ($required_channels as $channel) {
        if (!isUserMemberOfChannel($user_id, $channel['id'])) {
            $all_joined = false;
            $not_joined_channels_info[] = $channel;
        }
    }

    if ($all_joined) {
        $welcome_message = "ধন্যবাদ! আপনি প্রয়োজনীয় সকল চ্যানেলে জয়েন করেছেন।\n\nএবার আমাদের ওয়েবসাইট ভিজিট করুন:";
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 ওয়েবসাইট ভিজিট করুন', 'web_app' => ['url' => WEB_APP_URL]]
                ]
            ]
        ];
        sendMessage($chat_id, $welcome_message, $keyboard);
    } else {
        $join_message = "বটটি ব্যবহার করার জন্য অনুগ্রহ করে নিচের চ্যানেলগুলোতে জয়েন করুন:\n\n";
        $inline_keyboard_buttons = [];

        foreach ($not_joined_channels_info as $channel) {
            $join_message .= "➡️ <b>" . htmlspecialchars($channel['name']) . "</b>\n";
            $inline_keyboard_buttons[] = [['text' => '🔗 ' . htmlspecialchars($channel['name']), 'url' => $channel['url']]];
        }
        $join_message .= "\nসবগুলো চ্যানেলে জয়েন করার পর নিচের বাটনে ক্লিক করুন।";

        $inline_keyboard_buttons[] = [['text' => '✅ আমি জয়েন করেছি / রিচেক', 'callback_data' => 'check_join_status']];
        $keyboard = ['inline_keyboard' => $inline_keyboard_buttons];
        sendMessage($chat_id, $join_message, $keyboard);
    }
}


// --- Webhook সেটআপ এবং ইনপুট হ্যান্ডলিং ---
if (php_sapi_name() == 'cli') {
    // যদি CLI থেকে রান করা হয়, তবে কিছু করার নেই
    exit("This script is designed to be run as a web service.\n");
}

// Webhook সেটআপ করার জন্য (শুধু একবার রান করতে হবে)
// আপনার Render অ্যাপ URL হবে: https://your-app-name.onrender.com
// তখন ব্রাউজারে এই URL টি একবার ভিজিট করুন: https://your-app-name.onrender.com/index.php?setup=1
if (isset($_GET['setup'])) {
    if ($_GET['setup'] == '1' && BOT_TOKEN != 'YOUR_BOT_TOKEN') {
        $render_app_url = $_SERVER['HTTP_X_FORWARDED_PROTO'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        // নিশ্চিত করুন যে Render URL HTTPS ব্যবহার করছে
        if (strpos($render_app_url, '.onrender.com') !== false && strpos($render_app_url, 'https://') !== 0) {
             $render_app_url = 'https://' . substr($render_app_url, strpos($render_app_url, '://') + 3);
        }

        $response = apiRequest('setWebhook', ['url' => $render_app_url]);
        if ($response && $response['ok']) {
            echo "Webhook successfully set to: " . htmlspecialchars($render_app_url) . "<br>";
            echo "Response: " . htmlspecialchars(json_encode($response));
        } else {
            echo "Failed to set webhook.<br>";
            echo "URL tried: " . htmlspecialchars($render_app_url) . "<br>";
            echo "Response: " . htmlspecialchars(json_encode($response));
            if (BOT_TOKEN === 'YOUR_BOT_TOKEN') {
                echo "<br><b>Error: Please set your BOT_TOKEN in the script.</b>";
            }
        }
    } elseif (isset($_GET['setup']) && $_GET['setup'] == '0' && BOT_TOKEN != 'YOUR_BOT_TOKEN') {
        $response = apiRequest('deleteWebhook');
         if ($response && $response['ok']) {
            echo "Webhook successfully deleted.<br>";
            echo "Response: " . htmlspecialchars(json_encode($response));
        } else {
            echo "Failed to delete webhook.<br>";
            echo "Response: " . htmlspecialchars(json_encode($response));
        }
    } else {
        echo "To set up webhook, use ?setup=1. To delete, use ?setup=0. <br> Ensure BOT_TOKEN is set in the script.";
    }
    exit;
}


$update_json = file_get_contents('php://input');
if (empty($update_json)) {
    // যদি কোনো ইনপুট না থাকে (যেমন সরাসরি ব্রাউজারে ফাইল অ্যাক্সেস করা হলে)
    http_response_code(200); // Telegram কে জানাতে যে বট ঠিক আছে
    echo "Telegram Bot is listening... (Webhook should be POSTing here)";
    exit;
}

$update = json_decode($update_json, true);

if (!$update) {
    error_log("Failed to decode JSON input: " . $update_json);
    http_response_code(400); // Bad request
    exit;
}

if (LOG_UPDATES) {
    error_log("Received update: " . $update_json);
}

// Telegram সাধারণত কিছু সময় দেয় রেসপন্স করার জন্য।
// যদি আপনার প্রসেসিং বেশি সময় নেয়, আপনি প্রথমে একটি 200 OK রেসপন্স পাঠিয়ে দিতে পারেন
// এবং তারপর ব্যাকগ্রাউন্ডে কাজ করতে পারেন (Render-এ এটি কঠিন হতে পারে)।
// এই বটটির জন্য, এটি খুব দ্রুত কাজ করবে।
http_response_code(200); // টেলিগ্রামকে দ্রুত ফিডব্যাক দেওয়া যে আপডেট রিসিভ হয়েছে

processUpdate($update);

?>