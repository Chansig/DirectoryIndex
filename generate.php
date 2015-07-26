<?php

$content = sprintf("<?php\n\n// Generated by DirectoryIndex in %s\nnamespace Chansig\\DirectoryIndex;\n", (new \DateTime())->format('Y-m-d H:i:s'));
$directory = new \DirectoryIterator(__DIR__ . '/src/DirectoryIndex');

foreach ($directory as $fileInfo) {

    if ($fileInfo->isDot()) {
        continue;
    }
    if ($fileInfo->getFilename() === 'Settings.php') {
        continue;
    }
    $tmp = file_get_contents($fileInfo->getRealPath());
    $tmp = str_replace('<?php', '', $tmp);
    $tmp = str_replace('namespace Chansig\DirectoryIndex;', '', $tmp);
    $tmp = str_replace('?>', '', $tmp);
    $content .= $tmp;
}

$content .= "Main::Exec();\n";

while (strpos($content, "\r") !== false) {
    $content = str_replace("\r", "\n", $content);
}

while (strpos($content, "\n\n") !== false) {
    $content = str_replace("\n\n", "\n", $content);
}

$dir = __DIR__;
if ($argc && isset($argv[1])) {
    if (is_dir($argv[1])) {
        $dir = rtrim($argv[1], '/');
    }
}
file_put_contents($dir . '/index.php.dist', $content);
