<?php

use chillerlan\QRCode\{QRCode, QROptions};

/**
 * Class TOTPModule
 *
 * A minimal, dependency-free TOTP (RFC 6238) implementation for
 * optional two-factor login. Composer can't reach packagist from this
 * environment, so rather than add a library dependency that couldn't
 * be installed, this hand-rolls the handful of primitives a TOTP flow
 * actually needs: a random base32 secret, HOTP/TOTP code generation
 * (RFC 4226 dynamic truncation over HMAC-SHA1), a small verification
 * window to tolerate clock drift, and an otpauth:// provisioning URI
 * for authenticator apps (Google Authenticator, Authy, etc.) to scan.
 * Verified against the official RFC 6238 Appendix B test vector.
 */
class TOTPModule {
    const PERIOD = 30;
    const DIGITS = 6;
    const SECRET_BYTES = 20;

    /**
     * A fresh random secret, base32-encoded (the form authenticator
     * apps and the otpauth:// URI expect).
     *
     * @return string
     */
    public static function generateSecret()
    {
        return static::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * The current (or given) time step's 6-digit code for a base32
     * secret.
     *
     * @param $secret
     * @param $timestamp
     * @return string
     */
    public static function getCode($secret, $timestamp = null)
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD);

        $key = static::base32Decode($secret);

        // An 8-byte big-endian counter - pack('N*', 0, $counter) writes
        // two 4-byte big-endian words, i.e. a 64-bit counter whose top
        // 32 bits are always zero. That's fine here: $counter (a Unix
        // timestamp / 30) won't outgrow 32 bits until the year ~4147.
        $binCounter = pack('N*', 0, $counter);

        $hash = hash_hmac('sha1', $binCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $truncated % (10 ** self::DIGITS);

        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Accepts a code from the current time step or one step before/after
     * (+/- 30s), so a slightly-off device clock or the few seconds it
     * takes to type the code doesn't fail a correct entry.
     *
     * @param $secret
     * @param $code
     * @return bool
     */
    public static function verifyCode($secret, $code)
    {
        if ((!is_string($code)) || (strlen(trim($code)) === 0)) {
            return false;
        }

        $code = trim($code);
        $now = time();

        for ($step = -1; $step <= 1; $step++) {
            if (hash_equals(static::getCode($secret, $now + ($step * self::PERIOD)), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The otpauth:// URI an authenticator app scans (as a QR code) to
     * pick up a new secret.
     *
     * @param $secret
     * @param $email
     * @return string
     */
    public static function getProvisioningUri($secret, $email)
    {
        $label = rawurlencode('HortusFox:' . $email);
        $issuer = rawurlencode('HortusFox');

        return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . $issuer . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    /**
     * Renders an otpauth:// URI as a scannable QR code, as a base64 data
     * URI directly usable in an <img> tag - same rendering approach as
     * PlantsModel::generateQRCode().
     *
     * @param $uri
     * @return string
     */
    public static function getQrCodeDataUri($uri)
    {
        $options = new QROptions();
        $options->invertMatrix = true;

        $oqr = new QRCode($options);
        return $oqr->render($uri);
    }

    /**
     * A handful of one-time-use recovery codes, for when someone loses
     * access to their authenticator app. Returned in plaintext (shown to
     * the user once); the caller is responsible for storing only a
     * hashed form.
     *
     * @param $count
     * @return array
     */
    public static function generateRecoveryCodes($count = 8)
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            // xxxx-xxxx, hex - compact enough to type by hand if needed,
            // long enough (32 bits) to not be guessable.
            $codes[] = bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2));
        }

        return $codes;
    }

    /**
     * @param $data
     * @return string
     */
    private static function base32Encode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }

            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * @param $secret
     * @return string
     */
    private static function base32Decode($secret)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));

        $binaryString = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }

            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $data = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) === 8) {
                $data .= chr(bindec($byte));
            }
        }

        return $data;
    }
}
