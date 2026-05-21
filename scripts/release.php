<?php

declare(strict_types=1);


$lastTag = trim(shell_exec('git describe --tags --abbrev=0 2>/dev/null') ?? '');
$currentVersion = $lastTag ? ltrim($lastTag, 'v') : '0.0.0';

echo "Current version: {$currentVersion}\n";

$logCmd = $lastTag
    ? "git log {$lastTag}..HEAD --pretty=format:'%H|%s'"
    : "git log --pretty=format:'%H|%s'";

$lines = array_filter(
    explode("\n", trim(shell_exec($logCmd) ?? '')),
    fn($line) => $line !== ''
);

if (empty($lines)) {
    echo "No new commits since last tag. Skipping.\n";
    exit(0);
}

$commits = array_map(function (string $line): array {
    [$hash, $subject] = explode('|', $line, 2);
    return ['hash' => trim($hash), 'subject' => trim($subject)];
}, $lines);

$bump = determineBump($commits);
echo "Bump type: {$bump}\n";

$nextVersion = bumpVersion($currentVersion, $bump);
echo "Next version: {$nextVersion}\n";

writeVersion($nextVersion);
updateChangelog($nextVersion, $commits);
createGitTag($nextVersion);

echo "\nDone. Now run: git push && git push --tags\n";

// --- Functions ---

function determineBump(array $commits): string
{
    $major = false;
    $minor = false;

    foreach ($commits as ['subject' => $subject]) {
        if (str_contains($subject, 'BREAKING CHANGE') || preg_match('/^[a-z]+(\(.+\))?!:/', $subject)) {
            $major = true;
        }
        if (preg_match('/^feat(\(.+\))?[!:]/', $subject)) {
            $minor = true;
        }
    }

    return match (true) {
        $major => 'major',
        $minor => 'minor',
        default => 'patch',
    };
}

function bumpVersion(string $version, string $bump): string
{
    [$base] = explode('-', $version);
    [$major, $minor, $patch] = array_map('intval', explode('.', $base));

    return match ($bump) {
        'major' => ($major + 1) . '.0.0',
        'minor' => "{$major}." . ($minor + 1) . '.0',
        default => "{$major}.{$minor}." . ($patch + 1),
    };
}

function writeVersion(string $version): void
{
    updateJsonFile('composer.json', $version);

    if (file_exists('package.json')) {
        updateJsonFile('package.json', $version);
    }

    updatePluginVersion($version);
}

function updateJsonFile(string $path, string $version): void
{
    $json = json_decode(file_get_contents($path), true);
    $json['version'] = $version;
    file_put_contents(
        $path,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
    echo "  Updated {$path}\n";
}

function updatePluginVersion(string $version): void
{
    $candidates = array_merge(glob('index.php') ?: [], glob('src/*.php') ?: []);

    foreach ($candidates as $file) {
        $content = file_get_contents($file);
        $updated = preg_replace(
            "/'version'\s*=>\s*'[^']*'/",
            "'version' => '{$version}'",
            $content
        );
        if ($updated !== $content) {
            file_put_contents($file, $updated);
            echo "  Updated version in {$file}\n";
        }
    }
}

function updateChangelog(string $version, array $commits): void
{
    $sections = [
        'breaking' => ['title' => '⚠️ Breaking Changes', 'entries' => []],
        'feat'     => ['title' => '✨ Features',          'entries' => []],
        'fix'      => ['title' => '🐛 Bug Fixes',         'entries' => []],
        'perf'     => ['title' => '⚡ Performance',        'entries' => []],
        'refactor' => ['title' => '♻️ Refactoring',        'entries' => []],
        'docs'     => ['title' => '📖 Documentation',     'entries' => []],
        'chore'    => ['title' => '🔧 Chores',            'entries' => []],
        'other'    => ['title' => '📦 Other',             'entries' => []],
    ];

    $repoUrl = getRepoUrl();

    foreach ($commits as ['hash' => $hash, 'subject' => $subject]) {
        // Skip release commits
        if (str_starts_with($subject, 'chore(release):')) {
            continue;
        }

        $shortHash = substr($hash, 0, 7);
        $commitLink = $repoUrl
            ? "[`{$shortHash}`]({$repoUrl}/commit/{$hash})"
            : "`{$shortHash}`";

        $isBreaking = str_contains($subject, 'BREAKING CHANGE')
            || preg_match('/^[a-z]+(\(.+\))?!:/', $subject);

        // Parse "type(scope): message" or "type: message"
        preg_match('/^([a-z]+)(\(([^)]+)\))?!?:\s*(.+)$/', $subject, $m);
        $type    = $m[1] ?? 'other';
        $scope   = $m[3] ?? null;
        $message = $m[4] ?? $subject;

        $scopeStr = $scope ? "**{$scope}:** " : '';
        $entry    = "- {$scopeStr}{$message} ({$commitLink})";

        if ($isBreaking) {
            $sections['breaking']['entries'][] = $entry;
        } elseif (isset($sections[$type])) {
            $sections[$type]['entries'][] = $entry;
        } else {
            $sections['other']['entries'][] = $entry;
        }
    }

    // Build new changelog block
    $date = date('Y-m-d');
    $block = "## [{$version}] - {$date}\n\n";

    foreach ($sections as $section) {
        if (empty($section['entries'])) {
            continue;
        }
        $block .= "### {$section['title']}\n\n";
        $block .= implode("\n", $section['entries']) . "\n\n";
    }

    // Prepend to existing CHANGELOG.md or create it
    $file = 'CHANGELOG.md';
    $existing = file_exists($file) ? file_get_contents($file) : '';

    if ($existing === '') {
        $existing = "# Changelog\n\nAll notable changes to this project will be documented in this file.\n\n";
    }

    // Insert after the first heading
    if (preg_match('/^(#[^\n]+\n(?:[^\n]*\n)*?\n)/m', $existing, $m)) {
        $header  = $m[1];
        $rest    = substr($existing, strlen($header));
        $content = $header . $block . $rest;
    } else {
        $content = $existing . $block;
    }

    file_put_contents($file, $content);
    echo "  Updated CHANGELOG.md\n";
}

function getRepoUrl(): ?string
{
    $remote = trim(shell_exec('git remote get-url origin 2>/dev/null') ?? '');

    if ($remote === '') {
        return null;
    }

    // SSH → HTTPS
    if (preg_match('/^git@([^:]+):(.+)\.git$/', $remote, $m)) {
        return "https://{$m[1]}/{$m[2]}";
    }

    return rtrim(preg_replace('/\.git$/', '', $remote), '/');
}

function createGitTag(string $version): void
{
    $tag = "v{$version}";

    shell_exec('git add composer.json package.json CHANGELOG.md');
    shell_exec('git add $(git ls-files --modified "*.php") 2>/dev/null');

    $staged = trim(shell_exec('git diff --cached --name-only') ?? '');
    if ($staged !== '') {
        shell_exec("git commit -m 'chore(release): {$tag}'");
        echo "  Committed version bump\n";
    }

    shell_exec("git tag {$tag}");
    echo "  Created tag {$tag}\n";
}
