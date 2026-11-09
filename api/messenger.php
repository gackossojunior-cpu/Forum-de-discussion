<?php
require_once __DIR__ . '/../actions/database.php';

use Forum\Infrastructure\Realtime;
use Forum\Repositories\MessagingRepository;
use Forum\Repositories\NotificationRepository;

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['auth']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Connectez-vous pour accéder à la messagerie.']);
    exit;
}

$userId = (int)$_SESSION['id'];
$messages = new MessagingRepository($bdb);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (isset($_GET['conversation_id'])) {
        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
        $before = filter_input(INPUT_GET, 'before', FILTER_VALIDATE_INT) ?: null;
        if (!$conversationId || $conversationId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Conversation invalide.']);
            exit;
        }

        try {
            echo json_encode([
                'messages' => $messages->messagesForConversation($conversationId, $userId, $before),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (RuntimeException $exception) {
            http_response_code(403);
            echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    $csrfToken = $_SESSION['messenger_csrf_token'] ??= bin2hex(random_bytes(32));
    echo json_encode([
        'user' => ['id' => $userId, 'name' => $_SESSION['pseudo'] ?? ''],
        'csrfToken' => $csrfToken,
        'conversations' => $messages->conversationsForUser($userId),
        'contacts' => $messages->contactsForUser($userId, trim($_GET['search'] ?? '')),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['messenger_csrf_token'])
    || !is_string($csrfToken)
    || !hash_equals($_SESSION['messenger_csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Votre session a expiré. Rechargez la page puis réessayez.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide.']);
    exit;
}

try {
    switch ($payload['action'] ?? '') {
        case 'create_conversation':
            if (!is_array($payload['memberIds'] ?? null)) {
                throw new RuntimeException('Sélectionnez les participants de la conversation.');
            }
            $type = $payload['type'] ?? 'direct';
            $title = $payload['title'] ?? null;
            if (!is_string($type) || ($title !== null && !is_string($title))) {
                throw new RuntimeException('Les informations de la conversation sont invalides.');
            }
            $conversationId = $messages->createConversation(
                $userId,
                $payload['memberIds'],
                $type,
                $title
            );
            $memberIds = $messages->conversationMembers($conversationId);
            Realtime::emit([
                'type' => 'conversation:new',
                'conversationId' => $conversationId,
                'userIds' => $memberIds,
            ]);
            echo json_encode(['ok' => true, 'conversationId' => $conversationId]);
            break;

        case 'send_message':
            if (!is_string($payload['body'] ?? null)) {
                throw new RuntimeException('Le message doit être du texte.');
            }
            $conversationId = filter_var($payload['conversationId'] ?? null, FILTER_VALIDATE_INT);
            if (!$conversationId || $conversationId < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Conversation invalide.']);
                break;
            }
            $message = $messages->sendMessage($conversationId, $userId, $payload['body'] ?? '');
            $event = [
                'id' => (int)$message['id'],
                'conversationId' => (int)$message['conversation_id'],
                'senderId' => (int)$message['sender_id'],
                'senderName' => $message['sender_name'],
                'body' => $message['body'],
                'type' => $message['message_type'],
                'createdAt' => $message['date_creation'],
                'readCount' => 0,
            ];
            Realtime::emit(['type' => 'message:new', 'message' => $event]);

            foreach ($messages->conversationMembers($conversationId) as $memberId) {
                if ($memberId === $userId) {
                    continue;
                }
                $notificationId = (new NotificationRepository($bdb))->create(
                    $memberId,
                    $message['sender_name'],
                    mb_substr($message['body'], 0, 180)
                );
                Realtime::emit([
                    'type' => 'notification:new',
                    'notification' => [
                        'id' => $notificationId,
                        'userId' => $memberId,
                        'title' => $message['sender_name'],
                        'message' => mb_substr($message['body'], 0, 180),
                        'dateLabel' => date('d/m/Y à H:i'),
                        'url' => '/index.php?conversation_id=' . $conversationId,
                    ],
                ]);
            }

            echo json_encode(['ok' => true, 'message' => $event], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'mark_read':
            $conversationId = filter_var($payload['conversationId'] ?? null, FILTER_VALIDATE_INT);
            if (!$conversationId || $conversationId < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Conversation invalide.']);
                break;
            }
            $lastReadMessageId = $messages->markRead($conversationId, $userId);
            if ($lastReadMessageId !== null) {
                Realtime::emit([
                    'type' => 'message:read',
                    'conversationId' => $conversationId,
                    'userId' => $userId,
                    'messageId' => $lastReadMessageId,
                ]);
            }
            echo json_encode(['ok' => true, 'messageId' => $lastReadMessageId]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action de messagerie inconnue.']);
    }
} catch (RuntimeException $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
