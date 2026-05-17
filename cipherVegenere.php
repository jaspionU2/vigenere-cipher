<?php

const IC_PORTUGUESE = 0.072;
const IC_RANDOM = 0.038;

function gcd(int $a, int $b): int
{
    while ($b !== 0) {
        $temp = $b;
        $b = $a % $b;
        $a = $temp;
    }
    return $a;
}

function sanitizeText(string $text)
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

    $text = implode("", array_filter(str_split($text), function ($value) {
        return ctype_alnum($value);
    }));

    $text = preg_replace('/[^a-zA-Z0-9]/', '', $text);

    return $text;
}

function generateKey(string $msg, string $key)
{
    if (preg_match('/[^a-zA-Z0-9]/', $key)) {
        return null;
    }

    $keySize = strlen($key);

    for ($i = 0;; $i++) {
        if ($keySize == $i) {
            $i = 0;
        }

        if (strlen($key) == strlen($msg)) {
            break;
        }

        $key .= $key[$i];
    }

    return $key;
}

function encryptMessage(string $msg, string $key)
{
    $msg = strtolower(sanitizeText($msg));
    $key = generateKey($msg, strtolower($key));

    if ($key === null) {
        return null;
    }

    $encryptedResult = '';

    for ($i = 0; strlen($msg) > $i; $i++) {

        if (preg_match('/[0-9]/', $msg[$i])) {
            $encryptedResult .= (string) $msg[$i];
            continue;
        }

        $msgChar = ord($msg[$i]) - ord('a');
        $keyChar = ord($key[$i]) - ord('a');
        $mod = ($msgChar + $keyChar) % 26;
        $asciiChar = chr(ord('a') + $mod);

        $encryptedResult .= $asciiChar;
    }

    return $encryptedResult;
}

function decryptMessage(string $cipherText, string $key)
{
    $cipherText = strtolower(sanitizeText($cipherText));
    $key = generateKey($cipherText, strtolower($key));

    if ($key === null) {
        return null;
    }

    $plainText = '';

    for ($i = 0; strlen($cipherText) > $i; $i++) {

        if (preg_match('/[0-9]/', $cipherText[$i])) {
            $plainText .= (string) $cipherText[$i];
            continue;
        }

        $cipherChar = ord($cipherText[$i]) - ord('a');
        $keyChar = ord($key[$i]) - ord('a');
        $mod = (($cipherChar - $keyChar) % 26 + 26) % 26;;
        $asciiChar = chr(ord('a') + $mod);

        $plainText .= $asciiChar;
    }

    return $plainText;
}

function calculateIndexCoincidence(array $frequencyLetter, int $lengthText)
{
    $sumOfFrequency = array_reduce($frequencyLetter, function ($carry, $item) {
        return $carry + ($item * ($item - 1));
    }, 0);

    return $sumOfFrequency / ($lengthText * ($lengthText - 1));
}

function splitInCosets(string $text, int $length, bool $calculateFrequency = false)
{
    $text = strtolower(sanitizeText($text));
    $textLength = strlen($text);
    $cosets = [];
    $letterFrequency = null;

    if ($calculateFrequency) {
        $letterFrequency = array_fill(0, $length, array_fill(0, 26, 0));
    }

    for ($i = 0; $i < $length; $i++) {
        $cosets[$i] = "";

        for ($j = $i; $j < $textLength; $j += $length) {
            $cosets[$i] .= $text[$j];

            if ($calculateFrequency) {
                $char = ord($text[$j]) - ord('a');
                $letterFrequency[$i][$char]++;
            }
        }
    }

    return ['cosets' => $cosets, 'frequency' => $letterFrequency];
}

function shiftCipherText(string $cipherText)
{
    $cipherText = strtolower(sanitizeText($cipherText));

    $icFromCosets = [];

    for ($k = 2; $k <= 20; $k++) {
        $cosets = [];
        $letterFrequency = array_fill(0, $k, array_fill(0, 26, 0));


        ['cosets' => $cosets, 'frequency' => $letterFrequency] = splitInCosets($cipherText, $k, true);

        $tempFrequency = [];
        foreach ($cosets as $index => $coset) {
            $cosetLength = strlen($coset);
            if ($cosetLength > 1) {
                $tempFrequency[] = calculateIndexCoincidence($letterFrequency[$index], $cosetLength);
            }
        }

        if (count($tempFrequency) > 0) $icFromCosets[$k] = array_sum($tempFrequency) / count($tempFrequency);
    }

    return $icFromCosets;
}

function findKeyLength(string $cipherText)
{
    $cipherText = strtolower(sanitizeText($cipherText));

    $icKeys = shiftCipherText($cipherText);

    $probableOffsets = array_filter($icKeys, function ($value) {
        $distanceNaturalText = abs($value - IC_PORTUGUESE);
        $distanceRandomText = abs($value - IC_RANDOM);

        return ($value >= 0.060 && $value <= 0.080) ||
            ($distanceNaturalText < $distanceRandomText && $value > 0.055);
    });

    if (empty($probableOffsets)) return null;

    arsort($probableOffsets);

    $sortedKeys = array_keys($probableOffsets);

    if (count($sortedKeys) === 1) {
        return $sortedKeys[0];
    }

    $topCandidates = array_slice($sortedKeys, 0, 4);

    $keyLength = array_reduce($topCandidates, function ($accumulator, $currentKey) {
        return gcd($accumulator, $currentKey);
    }, $topCandidates[0]);

    return $keyLength;
}

function calculateQuiQuad(array $letterFrequency, array $expectedFrequency)
{
    $sum = 0;
    foreach ($letterFrequency as $letter => $frequency) {
        $sum += (($frequency - $expectedFrequency[$letter]) ** 2) / $expectedFrequency[$letter];
    }

    return $sum;
}

function recoveryKeyword(int $probableKeyLength, string $cipherText)
{
    $cipherText = strtolower(sanitizeText($cipherText));

    $freqPortuguese = [
        'a' => 0.1463,
        'b' => 0.0104,
        'c' => 0.0388,
        'd' => 0.0499,
        'e' => 0.1257,
        'f' => 0.0102,
        'g' => 0.0130,
        'h' => 0.0128,
        'i' => 0.0618,
        'j' => 0.0040,
        'k' => 0.0002,
        'l' => 0.0278,
        'm' => 0.0474,
        'n' => 0.0505,
        'o' => 0.1073,
        'p' => 0.0252,
        'q' => 0.0120,
        'r' => 0.0653,
        's' => 0.0781,
        't' => 0.0434,
        'u' => 0.0463,
        'v' => 0.0167,
        'w' => 0.0001,
        'x' => 0.0021,
        'y' => 0.0001,
        'z' => 0.0047
    ];

    [
        'cosets' => $cosets,
        'frequency' => $letterFrequency
    ] = splitInCosets($cipherText, $probableKeyLength, true);

    $keyword = "";

    foreach ($cosets as $index => $coset) {
        $quiQuad = [];
        $lenCoset = strlen($coset);

        for ($shift = 0; $shift < 26; $shift++) {
            $observedLetterFrequency = [];
            $expectedFrequency = [];

            for ($i = 0; $i < 26; $i++) {
                if ($letterFrequency[$index][$i] > 0) {
                    $observedLetterFrequency[chr(97 + $i)] = $letterFrequency[$index][($i + $shift) % 26];
                }
                $expectedFrequency[chr(97 + $i)] = $lenCoset * $freqPortuguese[chr(97 + $i)];
            }
            $quiQuad[chr(97 + $shift)] = calculateQuiQuad($observedLetterFrequency, $expectedFrequency);
        }

        asort($quiQuad);
        $keyword .= key($quiQuad);
    }

    return $keyword;
}
