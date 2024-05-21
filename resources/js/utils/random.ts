/**
 * This is a hardfork of Prando, a pseudo-random number generator.
 * @source https://github.com/zeh/prando
 */

export default class Prando {
    private static readonly MIN: number = -2147483648; // Int32 min
    private static readonly MAX: number = 2147483647; // Int32 max

    private _seed: number;
    private _value = NaN;

    // ================================================================================================================
    // CONSTRUCTOR ----------------------------------------------------------------------------------------------------

    /**
     * Generate a new Prando pseudo-random number generator.
     *
     * @param seed - A number or string seed that determines which pseudo-random number sequence will be created. Defaults to a random seed based on `Math.random()`.
     */
    constructor(seed?: number | string) {
        if (typeof seed === 'string') {
            // String seed
            this._seed = this.hashCode(seed);
        } else if (typeof seed === 'number') {
            // Numeric seed
            this._seed = this.getSafeSeed(seed);
        } else {
            // Pseudo-random seed
            this._seed = this.getSafeSeed(
                Prando.MIN +
                    Math.floor((Prando.MAX - Prando.MIN) * Math.random())
            );
        }
        this.reset();
    }

    // ================================================================================================================
    // PUBLIC INTERFACE -----------------------------------------------------------------------------------------------

    /**
     * Generates a pseudo-random number between a lower (inclusive) and a higher (exclusive) bounds.
     *
     * @param min - The minimum number that can be randomly generated.
     * @param pseudoMax - The maximum number that can be randomly generated (exclusive).
     * @return The generated pseudo-random number.
     */
    public next(min = 0, pseudoMax = 1): number {
        this.recalculate();
        return this.map(this._value, Prando.MIN, Prando.MAX, min, pseudoMax);
    }

    /**
     * Generates a pseudo-random integer number in a range (inclusive).
     *
     * @param min - The minimum number that can be randomly generated.
     * @param max - The maximum number that can be randomly generated.
     * @return The generated pseudo-random number.
     */
    public nextInt(min = 10, max = 100): number {
        this.recalculate();
        return Math.floor(
            this.map(this._value, Prando.MIN, Prando.MAX, min, max + 1)
        );
    }

    /**
     * Generates a pseudo-random string sequence of a particular length from a specific character range.
     *
     * Note: keep in mind that creating a random string sequence does not guarantee uniqueness; there is always a
     * 1 in (char_length^string_length) chance of collision. For real unique string ids, always check for
     * pre-existing ids, or employ a robust GUID/UUID generator.
     *
     * @param length - Length of the string to be generated.
     * @param chars - Characters that are used when creating the random string. Defaults to all alphanumeric chars (A-Z, a-z, 0-9).
     * @return The generated string sequence.
     */
    public nextString(
        length = 16,
        chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    ): string {
        let str = '';
        while (str.length < length) {
            str += this.nextChar(chars);
        }
        return str;
    }

    /**
     * Generates a pseudo-random string of 1 character specific character range.
     *
     * @param chars - Characters that are used when creating the random string. Defaults to all alphanumeric chars (A-Z, a-z, 0-9).
     * @return The generated character.
     */
    public nextChar(
        chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    ): string {
        return chars.substr(this.nextInt(0, chars.length - 1), 1);
    }

    /**
     * Picks a pseudo-random item from an array. The array is left unmodified.
     *
     * Note: keep in mind that while the returned item will be random enough, picking one item from the array at a time
     * does not guarantee nor imply that a sequence of random non-repeating items will be picked. If you want to
     * *pick items in a random order* from an array, instead of *pick one random item from an array*, it's best to
     * apply a *shuffle* transformation to the array instead, then read it linearly.
     *
     * @param array - Array of any type containing one or more candidates for random picking.
     * @return An item from the array.
     */
    public nextArrayItem<T>(array: T[]): T {
        return array[this.nextInt(0, array.length - 1)];
    }

    /**
     * Generates a pseudo-random boolean.
     *
     * @return A value of true or false.
     */
    public nextBoolean(): boolean {
        this.recalculate();
        return this._value > 0.5;
    }

    /**
     * Skips ahead in the sequence of numbers that are being generated. This is equivalent to
     * calling next() a specified number of times, but faster since it doesn't need to map the
     * new random numbers to a range and return it.
     *
     * @param iterations - The number of items to skip ahead.
     */
    public skip(iterations = 1): void {
        while (iterations-- > 0) {
            this.recalculate();
        }
    }

    /**
     * Reset the pseudo-random number sequence back to its starting seed. Further calls to next()
     * will then produce the same sequence of numbers it had produced before. This is equivalent to
     * creating a new Prando instance with the same seed as another Prando instance.
     *
     * Example:
     * let rng = new Prando(12345678);
     * console.log(rng.next()); // 0.6177754114889017
     * console.log(rng.next()); // 0.5784605181725837
     * rng.reset();
     * console.log(rng.next()); // 0.6177754114889017 again
     * console.log(rng.next()); // 0.5784605181725837 again
     */
    public reset(): void {
        this._value = this._seed;
    }

    // ================================================================================================================
    // PRIVATE INTERFACE ----------------------------------------------------------------------------------------------

    private recalculate(): void {
        this._value = this.xorshift(this._value);
    }

    private xorshift(value: number): number {
        // Xorshift*32
        // Based on George Marsaglia's work: http://www.jstatsoft.org/v08/i14/paper
        value ^= value << 13;
        value ^= value >> 17;
        value ^= value << 5;
        return value;
    }

    private map(
        val: number,
        minFrom: number,
        maxFrom: number,
        minTo: number,
        maxTo: number
    ): number {
        return (
            ((val - minFrom) / (maxFrom - minFrom)) * (maxTo - minTo) + minTo
        );
    }

    private hashCode(str: string): number {
        let hash = 0;
        if (str) {
            const l = str.length;
            for (let i = 0; i < l; i++) {
                hash = (hash << 5) - hash + str.charCodeAt(i);
                hash |= 0;
                hash = this.xorshift(hash);
            }
        }
        return this.getSafeSeed(hash);
    }

    private getSafeSeed(seed: number): number {
        if (seed === 0) return 1;
        return seed;
    }
}
