/**
 * City Directory — frontend interactivity
 *
 * Handles:
 *  - Live search filtering
 *  - Alphabet letter filtering
 *  - "View More" pagination
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const blocks = document.querySelectorAll( '.c-city-directory' );

	blocks.forEach( ( block ) => {
		const searchInput    = block.querySelector( '.c-city-directory__search' );
		const letterButtons  = block.querySelectorAll( '.c-city-directory__letter' );
		const cards          = Array.from( block.querySelectorAll( '.c-city-directory__card' ) );
		const viewMoreBtn    = block.querySelector( '.c-city-directory__view-more' );
		const viewMoreWrap   = block.querySelector( '.c-city-directory__view-more-wrap' );
		const itemsPerPage   = parseInt( block.dataset.itemsPerPage || '9', 10 );

		let activeLetter  = 'All';
		let searchQuery   = '';
		let visibleCount  = itemsPerPage;

		/**
		 * Determine if a card matches the current filter state.
		 *
		 * @param {Element} card
		 * @return {boolean}
		 */
		const cardMatches = ( card ) => {
			const name   = ( card.dataset.cityName || '' ).toLowerCase();
			const letter = ( card.dataset.letter || '' ).toUpperCase();

			const matchesSearch  = ! searchQuery || name.includes( searchQuery.toLowerCase() );
			const matchesLetter  = activeLetter === 'All' || letter === activeLetter;

			return matchesSearch && matchesLetter;
		};

		/**
		 * Re-render card visibility based on current filter + pagination state.
		 */
		const render = () => {
			const matching = cards.filter( cardMatches );
			const hidden   = cards.filter( ( c ) => ! cardMatches( c ) );

			// Hide non-matching cards entirely.
			hidden.forEach( ( card ) => {
				card.style.display = 'none';
				card.removeAttribute( 'data-visible' );
			} );

			// Show matching cards up to visibleCount.
			matching.forEach( ( card, i ) => {
				if ( i < visibleCount ) {
					card.style.display = '';
					card.dataset.visible = '1';
				} else {
					card.style.display = 'none';
					card.removeAttribute( 'data-visible' );
				}
			} );

			// Show/hide View More button.
			if ( viewMoreWrap ) {
				if ( matching.length > visibleCount ) {
					viewMoreWrap.style.display = '';
				} else {
					viewMoreWrap.style.display = 'none';
				}
			}
		};

		// --- Search ---
		if ( searchInput ) {
			searchInput.addEventListener( 'input', ( e ) => {
				searchQuery  = e.target.value.trim();
				visibleCount = itemsPerPage; // reset pagination on new search
				render();
			} );
		}

		// --- Alphabet filter ---
		letterButtons.forEach( ( btn ) => {
			btn.addEventListener( 'click', () => {
				activeLetter = btn.dataset.letter || 'All';
				visibleCount = itemsPerPage; // reset pagination on letter change

				// Update active state.
				letterButtons.forEach( ( b ) => {
					b.classList.toggle( '-active', b === btn );
					b.setAttribute( 'aria-pressed', b === btn ? 'true' : 'false' );
				} );

				render();
			} );
		} );

		// --- View More ---
		if ( viewMoreBtn ) {
			viewMoreBtn.addEventListener( 'click', () => {
				visibleCount += itemsPerPage;
				render();
			} );
		}

		// Initial render.
		render();
	} );
} );
