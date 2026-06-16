/**
 * @class ProgressCircleCounter
 * @classdesc Represents a progress circle counter that animates elements based on scrolling or intersection
 */

class ProgressCircleCounter {

    constructor(options) {
        this.defaults = {
            selector: '.c-counter__progress',
            item: {
                svg: '.c-counter__circle',
                text: '.c-counter__text',
            },
            svgClasses: {
                complete: '.complete',
                incomplete: '.incomplete',
                percentage: '.percentage',
            },
            duration: 2000,
            delay: 10,
            once: true,
            start: 0,
            percentage: 50,
            data: {
                percentage: 'data-progress-percentage',
            },
        };

        this.configOptions = { ...this.defaults, ...options || {} };
        this.registerEventListeners();
    }

    registerEventListeners() {
        const elements = document.querySelectorAll(this.configOptions.selector);
        const intersectionSupported = this.intersectionListenerSupported();

        if (intersectionSupported) {
            const intersectObserver = new IntersectionObserver(this.animateElements.bind(this), {
                root: null,
                rootMargin: '20px',
                threshold: 0.5,
            });

            elements.forEach((element) => {
                intersectObserver.observe(element);
            });
        } else if (window.addEventListener) {
            this.animateLegacy(elements);

            window.addEventListener('scroll', () => {
                this.animateLegacy(elements);
            }, { passive: true });
        }
    }

    animateLegacy(elements) {
        elements.forEach((element) => {
            if (this.elementIsInView(element)) {
                this.animateElements([element]);
            }
        });
    }

    animateElements(elements, observer) {
        elements.forEach((element) => {
            const elm = element.target || element;

            const elementConfig = this.parseConfig(elm);
            const elmText = element.target.querySelector(elementConfig.item.text);

            const elmSvg = element.target.querySelector(elementConfig.item.svg);
            const elmComplete = elmSvg.querySelector(elementConfig.svgClasses.complete);
            const elmPercentage = elmSvg.querySelector(elementConfig.svgClasses.percentage);
            const elmDashLength = Math.ceil(elmComplete.getTotalLength());

            const elmFill = parseFloat(elmDashLength - ((elementConfig.percentage * elmDashLength) / 100), 5);

            if (elmPercentage) {
                elmPercentage.style.strokeDashoffset = elmFill;
                elmPercentage.style.strokeDasharray = elmDashLength;
                elementConfig.fillLength = elmFill;
            }

            if (elmComplete) {
                elmComplete.style.strokeDasharray = elmDashLength;
                elmComplete.style.strokeDashoffset = elmDashLength;
                elementConfig.dashLength = elmDashLength;
            }

            elmSvg.classList.remove('not-ready');

            // If duration is less than or equal zero, just format the 'end' value
            if (elementConfig.duration <= 0) {
                elmComplete.style.strokeDashoffset = elmFill;
                return elmText.innerHTML = parseInt(elementConfig.percentage);
            }

            if ((!observer && !this.elementIsInView(element)) || (observer && element.intersectionRatio < 0.5)) {
                const value = elementConfig.percentage < elementConfig.start ? elementConfig.percentage : elementConfig.start;
                elmComplete.style.strokeDashoffset = parseFloat(elmDashLength - ((value * elmDashLength) / 100), 5);
                return elmText.innerHTML = parseInt(value);
            }

            // If duration is more than 0, then start the counter
            setTimeout(() => this.startCounter(elm, elmText, elmComplete, elementConfig), elementConfig.delay);
        });
    }

    startCounter(element, elementText, elementComplete, config) {
        // First, get the increments step
        let incrementsPerStep = (config.percentage - config.start) / (config.duration / config.delay);
        // Next, set the counter mode (Increment or Decrement)
        let countMode = 'inc';

        // Set mode to 'decrement' and 'increment step' to minus if start is larger than end
        if (config.start > config.percentage) {
            countMode = 'dec';
            incrementsPerStep *= -1;
        }

        // Next, determine the starting value
        let currentCount = this.parseValue(config.start);
        // And then print it's value to the page
        const currentFill = config.dashLength - ((config.start * config.dashLength) / 100);

        // console.log(currentFill, ' current fill');

        elementText.innerHTML = parseInt(currentCount);
        elementComplete.style.strokeDashoffset = parseFloat(currentFill, 5);

        // If the config 'once' is true, then set the 'duration' to 0
        if (config.once === true) {
            element.setAttribute('data-progress-duration', 0);
        }

        // Now, start counting with counterWorker using Interval method based on delay
        const counterWorker = setInterval(() => {
            // First, determine the next value base on current value, increment value, and cound mode
            const nextNum = this.nextNumber(currentCount, incrementsPerStep, countMode);

            const nextFill = config.dashLength - ((nextNum * config.dashLength) / 100);
            // console.log(nextFill, 'next fill');
            // Next, print that value to the page
            elementText.innerHTML = parseInt(nextNum);
            elementComplete.style.strokeDashoffset = parseFloat(nextFill, 5);
            // Now set that value to the current value, becouse it's already printed
            currentCount = nextNum;

            // If the value is larger or less than the 'end' (base on mode), then  print the end value and stop the Interval
            if ((currentCount >= config.percentage && countMode === 'inc') || (currentCount <= config.percentage && countMode === 'dec')) {
                elementText.innerHTML = parseInt(config.percentage);
                elementComplete.style.strokeDashoffset = parseFloat(config.dashLength - ((config.percentage * config.dashLength) / 100), 5);
                clearInterval(counterWorker);
            }
        }, config.delay);
    }

    parseConfig(element) {
        const baseConfig = { ...this.configOptions };

        const configValues = [].filter.call(element.attributes, (attr) => /^data-progress-/.test(attr.name));

        const elementConfig = {};

        configValues.forEach((e) => {
            const name = e.name.replace('data-progress-', '').toLowerCase();
            // eslint-disable-next-line radix
            const value = name === 'duration' ? parseInt(this.parseValue(e.value) * 1000) : this.parseValue(e.value);
            elementConfig[name] = value;
        });

        elementConfig.percentage > 100 ? elementConfig.percentage = 100 : elementConfig.percentage;
        elementConfig.start < 0 ? elementConfig.start = 0 : elementConfig.start;

        return Object.assign(baseConfig, elementConfig);
    }

    /** This function is to get the next number */
    nextNumber(number, steps, mode = 'inc') {
        // First, get the exact value from the number and step (int or float)
        number = this.parseValue(number);
        steps = this.parseValue(steps);

        // Last, get the next number based on current number, increment step, and count mode
        // Always return it as float
        return parseFloat(mode === 'inc' ? (number + steps) : (number - steps));
    }

    /** This function is to get the parsed value */
    parseValue(data) {
        // If number with dot (.), will be parsed as float
        if (/^[0-9]+\.[0-9]+$/.test(data)) {
            return parseFloat(data);
        }
        // If just number, will be parsed as integer
        if (/^[0-9]+$/.test(data)) {
            return parseInt(data);
        }
        // If it's boolean string, will be parsed as boolean
        if (/^true|false/i.test(data)) {
            return /^true/i.test(data);
        }
        // Return it's value as default
        return data;
    }

    /** This function is to detect the element is in view or not. */
    elementIsInView(element) {
        let top = element.offsetTop;
        let left = element.offsetLeft;
        const width = element.offsetWidth;
        const height = element.offsetHeight;

        while (element.offsetParent) {
            element = element.offsetParent;
            top += element.offsetTop;
            left += element.offsetLeft;
        }

        return (
            top >= window.pageYOffset
            && left >= window.pageXOffset
            && (top + height) <= (window.pageYOffset + window.innerHeight)
            && (left + width) <= (window.pageXOffset + window.innerWidth)
        );
    }

    /** Just some condition to check browser Intersection Support */
    intersectionListenerSupported() {
        return ('IntersectionObserver' in window)
            && ('IntersectionObserverEntry' in window)
            && ('intersectionRatio' in window.IntersectionObserverEntry.prototype);
    }

}

export default ProgressCircleCounter;
