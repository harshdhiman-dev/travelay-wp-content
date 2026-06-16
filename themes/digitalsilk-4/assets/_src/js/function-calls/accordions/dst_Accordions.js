import DSMPAccordions from '../../library/tabs-accordions/DSMPAccordions';

const accordionID = 'js-acc';
const accordionSelector = '.js-acc-wrapper';
const accordionItems = document.querySelectorAll(accordionSelector);

const createAccordions = () => {
    const accordions = [];

    accordionItems.forEach((acc, i) => {
        let accID = `${accordionID}-${i}`;
        let callID = `#${accID}`;
        acc.setAttribute('id', accID);

        accordions[i] = new DSMPAccordions(callID);

        //Uncomment if an event is needed to re init accordions, ex: when using load more for faqs
        // acc.addEventListener('re-init', event => {
        //     accordions[i].reInit();
        // })
    });
};

export {
    createAccordions,
};
