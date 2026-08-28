<?php
declare(strict_types=1);

/**
 * mrl_theme_helper.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/27/2026 5:27:00 pm
 */

function mrl_theme_options(): array
{
    return [
        'cars' => 'Cars',
        'starry-night' => 'Starry Night',
        'dark' => 'Dark',
        'light' => 'Light',
    ];
}

function mrl_theme_normalize(string $theme): string
{
    $theme = strtolower(trim($theme));
    return array_key_exists($theme, mrl_theme_options()) ? $theme : 'dark';
}

function mrl_theme_get(PDO $dbo, int $userID): string
{
    if ($userID <= 0) {
        return 'dark';
    }

    try {
        $stmt = $dbo->prepare(
            "SELECT team_theme
             FROM mrl_user_preferences
             WHERE userID = :uid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? mrl_theme_normalize((string)($row['team_theme'] ?? 'dark'))
            : 'dark';
    } catch (Throwable $e) {
        return 'dark';
    }
}

function mrl_theme_save(PDO $dbo, int $userID, string $theme): bool
{
    if ($userID <= 0) {
        return false;
    }

    $theme = mrl_theme_normalize($theme);

    try {
        $stmt = $dbo->prepare(
            "INSERT INTO mrl_user_preferences (userID, team_theme, updated_at)
             VALUES (:uid, :theme, NOW())
             ON DUPLICATE KEY UPDATE
                team_theme = VALUES(team_theme),
                updated_at = NOW()"
        );

        return $stmt->execute([
            ':uid' => $userID,
            ':theme' => $theme,
        ]);
    } catch (Throwable $e) {
        return false;
    }
}
