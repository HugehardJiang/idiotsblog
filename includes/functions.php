<?php
// Helper functions

function slugify($text)
{
    // If it contains Chinese characters, return the text directly
    if (preg_match("/\p{Han}+/u", $text)) {
        return trim($text);
    }

    // Replace non-letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a-' . time();
    }

    return $text;
}

function truncate($text, $limit = 150)
{
    if (strlen($text) > $limit) {
        $text = substr($text, 0, $limit) . '...';
    }
    return $text;
}

// Simple recursive function to build comment tree
function buildCommentTree(array $elements, $parentId = null)
{
    $branch = array();

    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCommentTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}

// Render Markdown (basic wrapper, in real usage might use a lib)
// For this project, we'll use a javascript-based renderer on frontend, 
// but if backend text processing is needed, we can add a simple regex parser or just return raw.
// We'll trust the frontend JS (marked.js) for the rich rendering to keep PHP simple.
function renderMarkdown($text)
{
    // This is a placeholder if we ever need server-side rendering.
    // Ideally we store MD and render on client or server. 
    // For 'Nature' style, client side with MathJax is robust.
    return htmlspecialchars($text);
}
?>