<?php
$content = file_get_contents('../ConfirmDispatch_Invoice.php');
preg_match_all('/INSERT\s+INTO\s+[a-zA-Z0-9_]+\s*\(([^)]+)\)\s*(?:VALUES\s*\((.*?)\)|SELECT\s+(.*?)FROM)/is', $content, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $columns = count(explode(',', $match[1]));
    if (!empty($match[2])) {
        // Values count is tricky with quotes/functions. Let's just do a naive split by comma
        // But better yet, I can just look at the exact text.
        echo "Query: " . substr($match[0], 0, 50) . "...\n";
        echo "Columns: " . $columns . "\n";
    } elseif (!empty($match[3])) {
        echo "Query (SELECT): " . substr($match[0], 0, 50) . "...\n";
        $selects = count(explode(',', $match[3]));
        echo "Columns: $columns, Selects: $selects\n";
    }
}
