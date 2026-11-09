<?php
require_once __DIR__ . '/actions/database.php';

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id || $user_id < 1) {
    header('Location: index.php');
    exit;
}

$userRepository = new Forum\Repositories\UserRepository($bdb);
$questionRepository = new Forum\Repositories\QuestionRepository($bdb);
$user = $userRepository->findProfile($user_id);
if (!$user) {
    header('Location: index.php');
    exit;
}

$questions = $questionRepository->forUser($user_id);
$repliesCount = $userRepository->countReplies($user_id);

require __DIR__ . '/views/users/profile.php';
