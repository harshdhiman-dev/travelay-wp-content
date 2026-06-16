/**
 * Retrieves the value of a query parameter from the current URL.
 *
 * @param {string} name - The name of the query parameter.
 * @return {string} - The value of the query parameter or an empty string if it does not exist.
 */
const u_getParameterByName = (name) => {
    const queryString = window.location.search || window.location.hash.split('?')[1];
    if (queryString) {
        const urlParams = new URLSearchParams(queryString);
        const value = urlParams.get(name);

        return value !== null ? value : '';
    }

    return '';
};

export {
    u_getParameterByName,
};
