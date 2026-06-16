// Interacitvity for the DS Tabs block
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Dispatches a custom event when the active tab changes.
 *
 * @param {number} tabIndex         - The index of the active tab (1-based).
 * @param {number} previousTabIndex - The index of the previous tab (1-based).
 * @param {string} navType          - The type of navigation (e.g., 'tab', 'arrow', 'dropdown', etc..).
 * @param {Object} el               - The object returned by getElement().
 */
const broadcastTabChange = ( tabIndex, previousTabIndex, navType, el ) => {

	if ( ! el?.ref ) {
		return;
	}

	const parentBlock = el.ref.closest('.wp-block-ds-blocks-ds-tabs');

	if ( ! parentBlock ) {
		return;
	}

	// Get all panels inside the block
	const panelNodeList = parentBlock.querySelectorAll('[role="tabpanel"]');
	const allPanels = Array.from(panelNodeList); // convert NodeList to array

	// Determine active panel (if any)
	const activePanel = (
		typeof tabIndex === 'number' && tabIndex > 0 && allPanels[tabIndex - 1]
	) ? allPanels[tabIndex - 1] : null;

	const event = new CustomEvent(
		'dsTabsTabChange',
		{
			detail: {
				activeTab: tabIndex,
				previousTab: previousTabIndex,
				tabsBlock: parentBlock,
				triggerElement: el.ref,
                navType,
				activePanel,
				allPanels,
			},
		}
	);

	window.dispatchEvent(event);
};

/**
 * Example 1: Track tab changes with optional analytics
 *
 * Sends an event to console (or external tracker) whenever a tab changes.
 * You can integrate Google Analytics, GTM, or any other custom tracking logic here.
 */
/*
window.addEventListener('dsTabsTabChange', (event) => {
	const { activeTab, navType } = event.detail;

	console.log(`[Analytics] Tab ${activeTab} opened via ${navType}`);

	// Example for GA4
	// gtag('event', 'tab_change', {
	//     tab_index: activeTab,
	//     navigation_type: navType
	// });
});
*/

/**
 * Example 2: Disable form submission if a specific tab is selected
 *
 * In this example, if tab index 3 is active, we disable the submit button inside that panel.
 */
/*
window.addEventListener('dsTabsTabChange', (event) => {
	const { activeTab, activePanel } = event.detail;

	if (activeTab === 3 && activePanel) {
		const submitButton = activePanel.querySelector('button[type="submit"]');
		if (submitButton) {
			submitButton.disabled = true;
			console.log('Submit button disabled on tab 3');
		}
	}
});
*/

/**
 * Example 3: Autoplay/pause video depending on active tab
 *
 * Any <video> elements in the active panel will autoplay (if allowed),
 * and any <video> elements in inactive panels will pause automatically.
 */
/*
window.addEventListener('dsTabsTabChange', (event) => {
	const { activePanel, allPanels } = event.detail;

	allPanels.forEach((panel) => {
		const video = panel.querySelector('video');
		if (!video) {
            return;
        }

		if (panel === activePanel) {
			// Try to autoplay (some browsers may block this unless muted)
			video.play().catch(
                () => {
				    console.warn('Autoplay was blocked or failed.');
			    }
            );
		} else {
			video.pause();
		}
	});
});
*/

/**
 * Example 4: Debug event payload
 *
 * Useful during development to see the full details of every tab interaction.
 */
/*
window.addEventListener('dsTabsTabChange', (event) => {
    const { activeTab, previousTab, tabsBlock, triggerElement, activePanel, allPanels, navType } = event.detail;
	console.group('[dsTabsTabChange]');
	console.log('Active Tab:', activeTab);
	console.log('Previous Tab:', previousTab);
	console.log('Tabs Block Element:', tabsBlock);
	console.log('Trigger Element:', triggerElement);
	console.log('Active Panel:', activePanel);
	console.log('All Panels:', allPanels);
	console.log('Navigation Type:', navType);
	console.groupEnd();
});
*/

store(
    'ds-tabs',
    {
        actions: {
            /**
             * Sets the currently selected tab index.
             * Triggered by clicking on a tab label.
             */
            setSelectedTab: () => {
                const context = getContext();
                const activeTab  = context.activeTab;
                const currentTab = context.currentTab;
                if ( activeTab !== currentTab ) {
                    // Set active tab on parent context
                    context.activeTab = context.currentTab;
                    // Get current element
                    const el = getElement();
                    broadcastTabChange(
                        currentTab,
                        activeTab,
                        'tab',
                        el
                    );
                }
            },
            /**
             * Toggles the accordion panel open or closed.
             * If the currently active tab is clicked again, it closes.
             */
            toggleAccordion: () => {
                const context = getContext();
                const parent = getContext('ds-tabs');
                const activeTab  = parent.activeTab;
                const currentTab = context.currentTabIndex;

                parent.activeTab = (activeTab === currentTab) ? null : currentTab;

                // Get current element
                const el = getElement();
                broadcastTabChange(
                    currentTab,
                    activeTab,
                    'accordion',
                    el
                );
            },
            /**
             * Sets the currently active tab from a <select> dropdown menu.
             * Used in mobile dropdown view.
             *
             * @param {Event} event - Change event from <select> element.
             */
            setTabFromDropdown: (event) => {
                const context = getContext();
                const selectedIndex = parseInt(event.target.value, 10);
                const activeTab = context.activeTab;
                const currentTab = selectedIndex;

                if ( activeTab !== currentTab ) {
                    // Set active tab on parent context
                    context.activeTab = currentTab;
                    // Get current element
                    const el = getElement();
                    broadcastTabChange(
                        currentTab,
                        activeTab,
                        'dropdown',
                        el
                    );
                }
            },
            /**
             * Moves to the next tab in the sequence.
             * If the active tab is the last tab, wraps back to the first tab.
             *
             * Uses 1-based indexing for tab identifiers.
             */
            nextTab: () => {
                const context = getContext();
                const tabCount = context.tabCount || 0;

                if (typeof context.activeTab === 'number' && tabCount > 0) {
                    let next = context.activeTab + 1;

                    if (next > tabCount) {
                        next = 1;
                    }

                    context.activeTab = next;

                    // Get current element
                    const el = getElement();
                    broadcastTabChange(
                        context.activeTab,
                        next,
                        'arrowNext',
                        el
                    );
                }
            },
            /**
             * Moves to the previous tab in the sequence.
             * If the active tab is the first tab, wraps around to the last tab.
             *
             * Uses 1-based indexing for tab identifiers.
             */
            previousTab: () => {
                const context = getContext();
                const tabCount = context.tabCount || 0;

                if (typeof context.activeTab === 'number' && tabCount > 0) {
                    let prev = context.activeTab - 1;

                    if (prev < 1) {
                        prev = tabCount;
                    }

                    context.activeTab = prev;

                    // Get current element
                    const el = getElement();
                    broadcastTabChange(
                        context.activeTab,
                        prev,
                        'arrowPrev',
                        el
                    );
                }
            },
        },
        callbacks: {
            /**
             * Checks if the current tab label is selected.
             * Used to apply the --active class and accessibility bindings.
             *
             * @return {boolean} Returns true if the current tab label is selected, otherwise false.
             */
            isTabSelected: () => {
                const context = getContext();
                return context.activeTab === context.currentTab;
            },
            /**
             * Returns correct tabindex for a tab label.
             * 0 = focusable, -1 = unfocusable via tab key.
             *
             * @return {string} Returns '0' if the tab is selected, otherwise '-1'.
             */
            getTabIndex: () => {
                const context = getContext();
                return context.activeTab === context.currentTab ? '0' : '-1';
            },
            /**
             * Determines whether a given tab panel should be visible.
             * Used to apply the --active class.
             *
             * @return {boolean} Returns true if the panel is active, otherwise false.
             */
            isPanelActive: () => {
                const tabContext = getContext();
                const parent = getContext('ds-tabs');
                return parent.activeTab === tabContext.currentTabIndex;
            },
            /**
             * Determines whether a given tab panel should be hidden.
             * Used to bind to the `hidden` attribute.
             *
             * @return {boolean} Returns true if the panel is hidden, otherwise false.
             */
            isPanelHidden: () => {
                const tabContext = getContext();
                const parent = getContext('ds-tabs');
                return parent.activeTab !== tabContext.currentTabIndex;
            },
            /**
             * Returns correct tabindex for a tab panel.
             * Ensures only the active panel is focusable via keyboard.
             *
             * @return {string} Returns '0' if the panel is active, otherwise '-1'.
             */
            getPanelTabIndex: () => {
                const tabContext = getContext();
                const parent = getContext('ds-tabs');
                return parent.activeTab === tabContext.currentTabIndex ? '0' : '-1';
            },
            /**
             * Scrolls the currently active tab label into view.
             * Triggered when `activeTab` matches the current tab label.
             * Uses the `scrollIntoView` method for smooth alignment in horizontal scroll containers.
             */
            scrollTabLabelIntoView: () => {
                const context = getContext();
                const el = getElement();

                if ( context.activeTab === context.currentTab && el?.ref ) {
                    /*el.ref.scrollIntoView(
                        {
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'start',
                        }
                    );*/
                }
            },
        },
    }
);
