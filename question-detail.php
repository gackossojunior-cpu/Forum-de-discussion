<?php
require_once __DIR__ . '/actions/database.php';

$question_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$question_id || $question_id < 1) {
    header('Location: index.php');
    exit;
}

$questionRepository = new Forum\Repositories\QuestionRepository($bdb);
$question = $questionRepository->findWithAuthor($question_id);
if (!$question) {
    header('Location: index.php');
    exit;
}

$replies = $questionRepository->repliesWithVoteCounts($question_id);
$realtimeEnabled = true;
$votes_question = [
    'like' => (int)$question['like_count'],
    'dislike' => (int)$question['dislike_count'],
];

require __DIR__ . '/views/questions/detail.php';
