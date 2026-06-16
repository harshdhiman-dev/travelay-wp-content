/**
 * Normalize a string by replacing "-" and "_" with spaces and capitalizing each word.
 *
 * @param {string} name The string to normalize.
 * @return {string} The normalized string.
 */
export const normalizeName = (name) => {
    return name
        .replace(/[-_]/g, ' ') // Replace "-" and "_" with spaces
        .replace(/\b\w/g, (char) => char.toUpperCase()); // Capitalize each word
};
