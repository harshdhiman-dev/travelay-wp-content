/**
 * Initializes GridderJS on elements with class "js-gridder".
 *
 * @returns {void}
 */
const dst_GridderInit = () => {
    const gridderElements = document.querySelectorAll('.js-gridder');

    if (gridderElements) {
        gridderElements.forEach((element) => {
            const columns = Number(element.dataset.gridderColumns) || 3; // set default to 3
            const gap = Number(element.dataset.gridderGap) || 15; // set default to 15

            new GridderJS(element, {
                columns,
                gap,
            });
        });
    }
};

export { dst_GridderInit };
