/**
 * A function that sets up the "read more" functionality.
 * @function
 * @name dst_ReadMore
 */
const dst_ReadMore = () => {
    const readMoreWrappers = document.querySelectorAll('.read-more-wrapper');
    if (!readMoreWrappers) return;

    readMoreWrappers.forEach((readMoreWrapper) => {
        const readMoreBtn = readMoreWrapper.querySelector('.js-read-more-toggle');
        const btnTextNoActive = readMoreBtn.getAttribute('data-show-less-text');
        const btnTextActive = readMoreBtn.children[0].textContent;
        const readMoreText = readMoreWrapper.querySelector('.read-more-text');
        readMoreBtn.addEventListener('click', () => {
            const isActive = readMoreWrapper.classList.contains('is-active');
            const readMoreTextHeight = readMoreText.scrollHeight;
            if (isActive) {
                readMoreWrapper.classList.remove('is-active');
                readMoreBtn.children[0].textContent = btnTextActive;
                readMoreText.style.maxHeight = 0;
            } else {
                readMoreWrapper.classList.add('is-active');
                readMoreBtn.children[0].textContent = btnTextNoActive;
                readMoreText.style.maxHeight = `${readMoreTextHeight}px`;
            }
        });
    });
};

export { dst_ReadMore };
