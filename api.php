<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sys.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
checkBlocked();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$user = getUser();

switch ($action) {

    case 'create_bot':
        $token = $_REQUEST['token'] ?? '';
        $botType = $_REQUEST['bot_type'] ?? '';
        $adminId = $_REQUEST['admin_id'] ?? '';
        $result = $_api('create_bot', [
            'token' => $token,
            'bot_type' => $botType,
            'admin_id' => $adminId,
        ]);
        if (!empty($result['success'])) {
            $bots = loadBots();
            $bots[] = [
                'bot_id' => $result['data']['bot_id'],
                'user_id' => $user['id'],
                'username' => $result['data']['bot_username'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            saveBots($bots);
        }
        echo json_encode($result);
        break;

    case 'get_bot_info':
        $botId = $_REQUEST['bot_id'] ?? '';
        echo json_encode($_api('get_bot_info', ['bot_id' => $botId]));
        break;

    case 'transfer_bot':
        $botId = $_REQUEST['bot_id'] ?? '';
        $targetChatId = $_REQUEST['target_chat_id'] ?? '';
        echo json_encode($_api('transfer_bot', [
            'bot_id' => $botId,
            'target_chat_id' => $targetChatId,
        ]));
        break;

    case 'delete_bot':
        $botId = $_REQUEST['bot_id'] ?? '';
        $result = $_api('delete_bot', ['bot_id' => $botId]);
        if (!empty($result['success'])) {
            $bots = loadBots();
            $bots = array_values(array_filter($bots, fn($b) =>
                $b['bot_id'] !== $botId || $b['user_id'] !== $user['id']
            ));
            saveBots($bots);
        }
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}
