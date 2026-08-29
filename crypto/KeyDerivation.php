<?php
declare(strict_types=1);

/**
 * Minimal RFC 6238 TOTP implementation.
 * No external library required.
 */
class TOTPHelper {

    private const DIGITS   = 6;
    private const PERIOD   = 30;
    private const ALGO     = 'sha1';

    public static function generateSecret(): string {
        return self::base32Encode(random_bytes(20));
    }

    public static function getCode(string $secret, int $offset = 0): string {
        $key       = self::base32Decode($secret);
        $counter   = intdiv(time(), self::PERIOD) + $offset;
        $msg       = pack('J', $counter);
        $hash      = hash_hmac(self::ALGO, $msg, $key, true);
        $offset    = ord($hash[19]) & 0x0f;
        $code      = (
            ((ord($hash[$offset])   & 0x7f) << 24) |
            ((ord($hash[$offset+1]) & 0xff) << 16) |
            ((ord($hash[$offset+2]) & 0xff) << 8)  |
             (ord($hash[$offset+3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool {
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::getCode($secret, $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function getUri(string $secret, string $username, string $issuer = 'EHRS'): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer), rawurlencode($username), $secret, rawurlencode($issuer)
        );
    }

    private static function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output   = '';
        $v = $b = 0;
        foreach (str_split($data) as $c) {
            $v = ($v << 8) | ord($c);
            $b += 8;
            while ($b >= 5) {
                $output .= $alphabet[($v >> ($b -= 5)) & 31];
            }
        }
        if ($b > 0) $output .= $alphabet[($v << (5 - $b)) & 31];
        return $output;
    }

    private static function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output   = '';
        $v = $b = 0;
        foreach (str_split(strtoupper($data)) as $c) {
            $pos = strpos($alphabet, $c);
            if ($pos === false) continue;
            $v = ($v << 5) | $pos;
            $b += 5;
            if ($b >= 8) {
                $output .= chr(($v >> ($b -= 8)) & 0xff);
            }
        }
        return $output;
    }
}
