<?php

namespace App;

use InvalidArgumentException;

/**
 * Custom password generator (task requirement #5).
 *
 * The caller asks for an exact number of lowercase, uppercase, digit and
 * special characters. An optional total length pads the result with extra
 * random characters drawn from the enabled categories. The final string is
 * shuffled with a cryptographically secure Fisher–Yates shuffle.
 *
 * Example: new PasswordGenerator(2, 3, 2, 2) -> 9 chars like "aF$3E.D5s"
 */
final class PasswordGenerator
{
    private const LOWER   = 'abcdefghijklmnopqrstuvwxyz';
    private const UPPER   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const DIGITS  = '0123456789';
    private const SPECIAL = '!@#$%^&*()-_=+[]{};:,.?';

    public function __construct(
        private int $lowercase,
        private int $uppercase,
        private int $digits,
        private int $special,
        private ?int $length = null
    ) {
        foreach (['lowercase', 'uppercase', 'digits', 'special'] as $field) {
            if ($this->$field < 0) {
                throw new InvalidArgumentException("$field count cannot be negative.");
            }
        }

        $minimum = $this->lowercase + $this->uppercase + $this->digits + $this->special;

        if ($minimum === 0) {
            throw new InvalidArgumentException('At least one character is required.');
        }
        if ($this->length === null) {
            $this->length = $minimum;
        }
        if ($this->length < $minimum) {
            throw new InvalidArgumentException(
                "Total length ($this->length) is smaller than the sum of the requested categories ($minimum)."
            );
        }
    }

    /**
     * Build the generator from percentages instead of fixed counts
     * (the "in percent" option mentioned in the task).
     */
    public static function fromPercentages(
        int $length,
        int $lowerPct,
        int $upperPct,
        int $digitPct,
        int $specialPct
    ): self {
        $lower   = (int) floor($length * $lowerPct / 100);
        $upper   = (int) floor($length * $upperPct / 100);
        $digits  = (int) floor($length * $digitPct / 100);
        $special = (int) floor($length * $specialPct / 100);

        return new self($lower, $upper, $digits, $special, $length);
    }

    public function generate(): string
    {
        $chars = [];

        $this->take($chars, self::LOWER, $this->lowercase);
        $this->take($chars, self::UPPER, $this->uppercase);
        $this->take($chars, self::DIGITS, $this->digits);
        $this->take($chars, self::SPECIAL, $this->special);

        // Pad with random characters from every category that was enabled.
        $pool = '';
        if ($this->lowercase > 0) { $pool .= self::LOWER; }
        if ($this->uppercase > 0) { $pool .= self::UPPER; }
        if ($this->digits    > 0) { $pool .= self::DIGITS; }
        if ($this->special   > 0) { $pool .= self::SPECIAL; }

        $padding = $this->length - count($chars);
        $this->take($chars, $pool, $padding);

        $this->shuffle($chars);

        return implode('', $chars);
    }

    /** Append $count random characters from $set into $chars. */
    private function take(array &$chars, string $set, int $count): void
    {
        $max = strlen($set) - 1;
        for ($i = 0; $i < $count; $i++) {
            $chars[] = $set[random_int(0, $max)];
        }
    }

    /** Cryptographically secure Fisher–Yates shuffle. */
    private function shuffle(array &$chars): void
    {
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
    }
}
