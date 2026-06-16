/**
 * Returns a column span value, ensuring it's a valid integer.
 * Returns `undefined` if the value is not a valid finite number.
 *
 * @param {?number} value Raw value.
 *
 * @return {number|undefined} Integer column span value.
 */
export const toColumnSpanValue = (value) => {
	const intValue = Number.parseInt(value, 10)
	return Number.isFinite(intValue) ? Math.max(1, Math.min(6, intValue)) : undefined
}

/**
 * Gets the effective column span for a block.
 *
 * @param {Object} block           - The block whose column span is to be calculated.
 * @param {number} totalBlockCount - The total number of blocks in the layout.
 *
 * @return {number} Effective column span value (1-6).
 */
export function getEffectiveColumnSpan(block, totalBlockCount) {
	// If columnSpan is defined, use it, otherwise calculate based on total blocks
	const { columnSpan } = block.attributes
	if (columnSpan !== undefined) {
		return toColumnSpanValue(columnSpan)
	}

	// Default distribution: divide 6 columns evenly among blocks
	return toColumnSpanValue(Math.floor(6 / totalBlockCount))
}

/**
 * Calculates the total column spans used by all blocks.
 *
 * @param {Array}  blocks                          - The array of blocks.
 * @param {number} [totalBlockCount=blocks.length] - The total number of blocks.
 * @return {number} The total column spans used (max 6).
 */
export function getTotalColumnsSpan(blocks, totalBlockCount = blocks.length) {
	return blocks.reduce((sum, block) => sum + getEffectiveColumnSpan(block, totalBlockCount), 0)
}

/**
 * Gets an object mapping each block's clientId to its column span.
 *
 * @param {Array}  blocks                          - An array of block objects.
 * @param {number} [totalBlockCount=blocks.length] - The total number of blocks.
 * @return {Object} An object mapping clientIds to column spans.
 */
export function getColumnSpans(blocks, totalBlockCount = blocks.length) {
	return blocks.reduce((accumulator, block) => {
		const columnSpan = getEffectiveColumnSpan(block, totalBlockCount)
		return Object.assign(accumulator, { [block.clientId]: columnSpan })
	}, {})
}

/**
 * Redistributes column spans evenly across blocks, ensuring the total is 6.
 *
 * @param {Object}  blocks          Block objects.
 * @param {?number} totalBlockCount Total number of blocks in Columns.
 *                                  Defaults to number of blocks passed.
 *
 * @return {Object} Redistributed column spans.
 */
export function getRedistributedColumnSpans(blocks, totalBlockCount = blocks.length) {
	// Calculate base span (how many columns each block gets)
	const baseSpan = Math.floor(6 / totalBlockCount)

	// Calculate remainder to distribute
	const remainder = 6 % totalBlockCount

	// Create mapping of clientIds to spans
	const spans = {}
	blocks.forEach((block, index) => {
		// Add one extra column to early blocks if there's a remainder
		spans[block.clientId] = baseSpan + (index < remainder ? 1 : 0)
	})

	return spans
}

/**
 * Checks if columns have explicit column spans set.
 *
 * @param {Object} blocks Block objects.
 *
 * @return {boolean} Whether columns have explicit spans.
 */
export function hasExplicitColumnSpans(blocks) {
	return blocks.every((block) => {
		const columnSpan = block.attributes.columnSpan
		return Number.isFinite(Number.parseInt(columnSpan, 10))
	})
}

/**
 * Updates blocks with new column spans.
 *
 * @param {Array}  blocks - The array of block objects.
 * @param {Object} spans  - An object mapping block client IDs to column spans.
 * @return {Array} - A new array of blocks with updated column spans.
 */
export function getMappedColumnSpans(blocks, spans) {
	return blocks.map((block) => ({
		...block,
		attributes: {
		...block.attributes,
		columnSpan: spans[block.clientId],
		},
	}))
}
