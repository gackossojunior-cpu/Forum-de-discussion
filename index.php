<?php
require_once __DIR__ . '/actions/database.php';

$questionRepository = new Forum\Repositories\QuestionRepository($bdb);
$search = trim($_GET['search'] ?? '');
$categorie = filter_input(INPUT_GET, 'categorie', FILTER_VALIDATE_INT) ?: 0;
$categories = $questionRepository->categories();
$questions = $questionRepository->search($search, $categorie);

require __DIR__ . '/views/questions/index.php';
