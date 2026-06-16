import { u_computedStyle, u_getElementHeight } from '../../utils/u_css';

/**
 * Represents a class for an absolute positioned newsletter element.
 * @constructor
 * @param {Object} options - The options for the newsletter element.
 * @param {string} options.selector - The CSS selector for the newsletter element.
 * @param {string} options.contentHolder - The CSS selector for the content holder element.
 */
class absPosNewsletter {
    constructor(options) {
        console.log('abs position');
        this.defaults = {
            selector:       '.footer-newsletter__inner',
            contentHolder:  '.site-content'
        }
        this.config = Object.assign({}, this.defaults, options || {});

        this.item = {};
        this.el = document.querySelector(this.config.selector);
        console.log(this.el);

        this.init();
    }

    init () {
        let self = this;
        self.calculateSize();
        self.lastDiv();
    }

    calculateSize () {
        let self = this;

        let elStyle = u_computedStyle(self.el);
        let height = u_getElementHeight(self.el);
        console.log(elStyle.height, height, elStyle);

        let matrix = new WebKitCSSMatrix(elStyle.transform);
        console.log(matrix.m42);

    }

    lastDiv () {
        let self = this;
        let content = document.querySelector(self.config.contentHolder);
        let contentChild = content.querySelector('div');

        console.log(contentChild.parentElement.children);

       // console.log(contentChild);
        // let contentLength = content.length;
        //
        // if(!contentLength > 1 || content[contentLength-1].nodeName != 'div')
        // {
        //     console.log(content[contentLength-1].nodeName);
        //     console.log(content);
        // }
        // else {
        //     console.log(content);
        // }
        //let contentDiv = content.querySelectorAll('div');

        //let contentLength = contentDiv.length;

        //console.log(contentLength, 'divs', contentDiv, content);
    }

}

export default absPosNewsletter;
