<?php
$content = file_get_contents('resources/views/dashboard.blade.php');
$lines = explode("\n", $content);
$stack = [];

foreach ($lines as $lineNum => $line) {
    if (preg_match('/@if/', $line)) {
        array_push($stack, ['type' => 'if', 'line' => $lineNum + 1]);
    } elseif (preg_match('/@endif/', $line)) {
        if (empty($stack)) {
            echo "Extra @endif at line " . ($lineNum + 1) . "\n";
        } else {
            array_pop($stack);
        }
    }
}

if (!empty($stack)) {
    echo "Unclosed @if statements:\n";
    foreach ($stack as $item) {
        echo "@if at line " . $item['line'] . "\n";
    }
} else {
    echo "All @if/@endif are balanced\n";
}
?>
