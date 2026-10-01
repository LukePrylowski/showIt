<?php
declare(strict_types=1);

function gameCount(bool $increment = false): int {
    $path = __DIR__.'/data/games-played.json';
    if (!$increment && !is_file($path)) return 0;
    $file = fopen($path, $increment ? 'c+' : 'r');
    if ($file === false) throw new RuntimeException('Cannot open game counter');
    try {
        if (!flock($file, $increment ? LOCK_EX : LOCK_SH)) throw new RuntimeException('Cannot lock game counter');
        $raw = stream_get_contents($file);
        $count = $raw === '' ? 0 : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_int($count) || $count < 0) throw new RuntimeException('Invalid game counter');
        if ($increment) {
            if ($count >= 9007199254740991) throw new RuntimeException('Game counter limit reached');
            $count++;
            $value = json_encode($count, JSON_THROW_ON_ERROR);
            rewind($file);
            if (fwrite($file, $value) !== strlen($value) || !ftruncate($file, strlen($value)) || !fflush($file)) {
                throw new RuntimeException('Cannot save game counter');
            }
        }
        return $count;
    } finally {
        fclose($file);
    }
}
