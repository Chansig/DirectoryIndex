<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$sourceFiles = [
    'Config.php',
    'DirectoryIndexException.php',
    'ResolvedDirectory.php',
    'PathResolver.php',
    'Entry.php',
    'DirectoryBrowser.php',
    'Sorter.php',
    'LocaleResolver.php',
    'Translator.php',
    'Response.php',
    'SecurityHeaders.php',
    'HtmlRenderer.php',
    'Application.php',
];
$imports = [];
$classes = [];

foreach ($sourceFiles as $sourceFile) {
    $source = file_get_contents($projectRoot . '/src/DirectoryIndex/' . $sourceFile);
    if ($source === false) {
        throw new RuntimeException(sprintf('Cannot read source file %s.', $sourceFile));
    }

    preg_match_all('/^use [^;]+;$/m', $source, $matches);
    foreach ($matches[0] as $import) {
        $imports[$import] = true;
    }

    $headerPattern = '/^<\?php\s+declare\(strict_types=1\);\s+'
        . 'namespace Chansig\\\\DirectoryIndex;\s*/';
    $source = preg_replace($headerPattern, '', $source);
    $source = preg_replace('/^use [^;]+;\s*$/m', '', (string) $source);
    $classes[] = trim((string) $source);
}

$css = file_get_contents($projectRoot . '/assets/style.css');
$svg = file_get_contents($projectRoot . '/assets/icons/sprite.svg');
if ($css === false || $svg === false) {
    throw new RuntimeException('Cannot read embedded assets.');
}

$configuration = <<<'PHP'
$directoryIndexConfig = [
    'forced_locale' => null,
    'fallback_locale' => 'en',
    'show_hidden' => false,
    'follow_symlinks' => false,
    'blocked_names' => [
        '.env', '.git', '.hg', '.svn', '.ssh', '.htaccess', '.htpasswd',
        'directory-index.config.php',
    ],
    'blocked_extensions' => ['key', 'pem', 'p12', 'pfx'],
];
PHP;

$bootstrap = <<<'PHP'
$application = new Application(
    __DIR__,
    Config::fromArray($directoryIndexConfig),
    DIRECTORY_INDEX_CSS,
    DIRECTORY_INDEX_SVG,
    basename(__FILE__),
);
$response = $application->handle($_SERVER, $_GET);
http_response_code($response->status);
foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}
echo $response->body;
PHP;

$output = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Chansig\\DirectoryIndex;\n\n";
$output .= implode("\n", array_keys($imports)) . "\n\n";
$output .= "// Configuration: edit these values in the distributed file if needed.\n" . $configuration . "\n\n";
$output .= implode("\n\n", $classes) . "\n\n";
$output .= "const DIRECTORY_INDEX_CSS = <<<'CSS'\n" . rtrim($css) . "\nCSS;\n\n";
$output .= "const DIRECTORY_INDEX_SVG = <<<'SVG'\n" . rtrim($svg) . "\nSVG;\n\n";
$output .= $bootstrap . "\n";

$distDirectory = $projectRoot . '/dist';
if (!is_dir($distDirectory) && !mkdir($distDirectory, 0777, true) && !is_dir($distDirectory)) {
    throw new RuntimeException('Cannot create dist directory.');
}

if (file_put_contents($distDirectory . '/index.php', $output) === false) {
    throw new RuntimeException('Cannot write dist/index.php.');
}

fwrite(STDOUT, "Generated dist/index.php\n");
