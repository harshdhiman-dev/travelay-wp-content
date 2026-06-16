/**
 * Handling custom CSS property for scrolling effects
 * @return {void}
 */

export function u_scrollEffect() {
    if (document.querySelector('.pr-scroll')) {
        window.addEventListener('scroll', () => {
            if (window.scrollY < 600) {
                document.querySelector('.pr-scroll').style.setProperty('--scrolly', window.scrollY);
            }
        }, { capture: false, passive: true });
    }
}
