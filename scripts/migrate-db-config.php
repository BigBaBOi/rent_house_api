<?php
// Script to migrate hardcoded DB config blocks to a centralized `config.php` include.
// Run from project root: `php scripts/migrate-db-config.php`

$root = realpath(__DIR__ . '/../');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$filesChanged = 0;

foreach ($it as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getRealPath();
    if (stripos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (stripos($path, DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR) !== false) continue;
    if (basename($path) === 'config.php') continue;
    if (substr($path, -4) !== '.php') continue;

    $content = file_get_contents($path);
    if (strpos($content, '$servername') === false) continue;
    if (strpos($content, 'new PDO(') === false) continue;

    $dir = dirname($path);
    $rel = str_replace($root, '', $dir);
    $rel = trim($rel, DIRECTORY_SEPARATOR);
    $depth = $rel === '' ? 0 : substr_count($rel, DIRECTORY_SEPARATOR) + 1;
    $prefix = '';
    if ($depth > 0) {
        $parts = array_fill(0, $depth, "..{}");
        // build a string like '/..'/..'
        $prefix = str_repeat('/..', $depth);
    }

    // determine require path using __DIR__ and relative path
    $requirePath = ($depth === 0) ? "__DIR__ . '/config.php'" : "__DIR__ . '{$prefix}/config.php'";

    // remove the block of variable assignments for DB
    $patternVars = '/\$servername\s*=.*?;\s*\$username\s*=.*?;\s*\$password\s*=.*?;\s*\$dbname\s*=.*?;\s*/is';
    $newInclude = "require_once {$requirePath};\n";
    $newContent = preg_replace($patternVars, $newInclude, $content, 1, $count);

    if ($count === 0) continue;

    // remove the new PDO(...) creation and optional setAttribute lines
    $patternPdo = '/\$conn\s*=\s*new\s+PDO\([\s\S]*?\);\s*(\$conn->setAttribute\([\s\S]*?\);\s*)?/is';
    $newContent = preg_replace($patternPdo, '', $newContent);

    // write backup and replace
    copy($path, $path . '.bak');
    file_put_contents($path, $newContent);
    $filesChanged++;
    echo "Updated: {$path}\n";
}

echo "Done. Files changed: {$filesChanged}\n";
