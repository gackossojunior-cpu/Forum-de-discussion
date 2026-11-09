<?php
function forum_escape($value)
{
    $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function forum_excerpt($value, $length = 200)
{
    $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = function_exists('mb_substr')
        ? mb_substr($text, 0, $length, 'UTF-8')
        : substr($text, 0, $length);

    $escapedExcerpt = htmlspecialchars($excerpt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $escapedExcerpt . (strlen($excerpt) < strlen($text) ? '...' : '');
}
