import { createHmac } from 'node:crypto';

const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32Decode(input: string): Buffer {
    const normalized = input
        .toUpperCase()
        .replace(/=+$/, '')
        .replace(/[^A-Z2-7]/g, '');
    let bits = 0;
    let value = 0;
    const bytes: number[] = [];
    for (const char of normalized) {
        value = (value << 5) | BASE32_ALPHABET.indexOf(char);
        bits += 5;
        if (bits >= 8) {
            bytes.push((value >>> (bits - 8)) & 0xff);
            bits -= 8;
        }
    }
    return Buffer.from(bytes);
}

/**
 * Generates a 6-digit TOTP code (RFC 6238, SHA-1, 30 second period) for the
 * given base32 secret — the "Setup Key" shown while enabling 2FA.
 */
export function generateTotpCode(base32Secret: string, atMs: number = Date.now()): string {
    const counter = Math.floor(atMs / 1000 / 30);
    const counterBuffer = Buffer.alloc(8);
    counterBuffer.writeBigUInt64BE(BigInt(counter));
    const digest = createHmac('sha1', base32Decode(base32Secret)).update(counterBuffer).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const code =
        ((digest[offset] & 0x7f) << 24) |
        ((digest[offset + 1] & 0xff) << 16) |
        ((digest[offset + 2] & 0xff) << 8) |
        (digest[offset + 3] & 0xff);
    return (code % 1_000_000).toString().padStart(6, '0');
}

/**
 * Generates a syntactically valid TOTP code that is guaranteed to be rejected,
 * by using a timestamp far outside the accepted verification window.
 */
export function generateInvalidTotpCode(base32Secret: string): string {
    const validNow = [
        generateTotpCode(base32Secret, Date.now() - 30_000),
        generateTotpCode(base32Secret),
        generateTotpCode(base32Secret, Date.now() + 30_000),
    ];
    for (let minutes = 10; ; minutes++) {
        const candidate = generateTotpCode(base32Secret, Date.now() + minutes * 60_000);
        if (!validNow.includes(candidate)) {
            return candidate;
        }
    }
}
