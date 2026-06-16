import { u_getParameterByName } from '../utils/u_params';

/**
 * Function to handle the load more functionality of blog posts
 *
 * @return {void}
 */
const dst_LoadMoreBlog = () => {
    (function ($) {
        const DSInitFilter = function (module) {
            const filter = {
                module: null,
                action: null,
                form: '',
                sort: null,
                clear: null,
                moreBtn: null,
                pagination: null,
                results: null,
                doing_ajax: null,
                timeout: null,
                query: {
                    post_type: null,
                    per_page: 9,
                    page: 1,
                    main_taxonomy: null,
                    secondary_taxonomy: null,
                },
                component_styles: {},
                page_num: 1,
                // eslint-disable-next-line no-undef
                ajax_url: ds.ajax_url,
                first_load: false,
                get_page_param: false,
                preloader: '<div class="filter-loader loader"><div class="spinner"><div class="double-bounce1"></div><div class="double-bounce2"></div></div><div class="loader-bg"></div></div>',
                // eslint-disable-next-line no-shadow
                init(module) {
                    const ajaxModule = $(module);
                    const initialPageNumber = u_getParameterByName('page_num');

                    if (ajaxModule) {
                        filter.module = ajaxModule;

                        filter.action = ajaxModule.data('action');
                        filter.query.page = initialPageNumber !== '' ? parseInt(initialPageNumber) : 1;
                        filter.query.post_type = ajaxModule.data('post-type');
                        filter.query.posts_per_page = ajaxModule.data('per-page');
                        filter.query.main_taxonomy = ajaxModule.data('main-taxonomy');
                        filter.query.secondary_taxonomy = ajaxModule.data('secondary-taxonomy');
                        filter.first_load = ajaxModule.data('first-load');
                        filter.get_page_param = ajaxModule.data('page-parameter') === true; // TODO build /page/* instead ?page=*

                        filter.parseURL(); // TODO

                        filter.initElementsActions();

                        filter.setComponentStyles();

                        if (filter.first_load) {
                            filter.sendAjax(u_getParameterByName('page_num'), true);
                            filter.first_load = false;
                        }
                    }
                },
                initElementsActions() {
                    const results = filter.module.find('div[data-container="ajax-result"]');

                    if (results.length > 0) {
                        filter.results = results;

                        const moreBtn = filter.module.find('a.ajax-load-more');
                        if (moreBtn) {
                            filter.moreBtn = moreBtn;
                            filter.morePosts();
                        }

                        const pagination = filter.module.find('div.ajax-links-pagination');
                        if (pagination) {
                            filter.pagination = pagination;
                            filter.paginate();
                        }

                        const clear = filter.module.find('a.ajax-clear-all');
                        if (clear) {
                            filter.clear = clear;
                            filter.clearFilter();
                        }

                        const sort = filter.module.find('select.ajax-sort-by');
                        if (sort) {
                            filter.sort = sort;
                            filter.sortPosts();
                        }

                        const form = filter.module.find('form[data-form="ajax"]');
                        if (form) {
                            filter.form = form;
                            filter.changeForm();
                        }
                    }
                },
                morePosts() {
                    filter.moreBtn.on('click', (e) => {
                        e.preventDefault();
                        filter.query.page++;
                        filter.sendAjax(filter.query.page, true, true);
                    });
                },
	            paginate() {
		            filter.pagination.find('span').unbind('click keyup');
		            filter.pagination.find('span').on('click keyup', (e) => {
			            if (e.type === 'click' || (e.type === 'keyup' && e.key === 'Enter')) {
				            e.preventDefault();
				            filter.query.page = $(e.currentTarget).data('page');

				            filter.sendAjax(filter.query.page, true, true);
				            const formElement = $('.js-ajax-block');
				            if (formElement.length) {
					            const topOffsetCss = getComputedStyle(document.documentElement).getPropertyValue('--dst--header-height');
					            const topOffset = parseInt(topOffsetCss) || 0;
					            const resultsTop = formElement.offset().top - topOffset;
					            $('html, body').animate({scrollTop: resultsTop}, 'smooth');
				            }
			            }
		            });
	            },
	            sortPosts() {
		            filter.sort.unbind('change');
		            filter.sort.not('[data-ajax="false"]').change(() => {
			            filter.sendAjax();
		            });
                },
                clearFilter() {
                    filter.clear.on('click', (e) => {
                        e.preventDefault();

                        $('input[type="text"], textarea', filter.form).val('');
                        $('input[type="radio"]', filter.form).removeAttr('checked');
                        $('input[type="checkbox"]', filter.form).removeAttr('checked');
                        $('select', filter.form).val('').removeAttr('selected');

                        filter.sort.val('').removeAttr('selected');

                        filter.sendAjax();
                    });
                },
                changeForm() {
                    const $input_text = filter.form.find('input[type="text"], textarea');
                    $input_text.unbind('keyup');
                    $input_text.not('[data-ajax="false"]').keyup((e) => {
                        if (e.keyCode === 13) {
                            return;
                        }

                        if (filter.timeout !== null) {
                            clearTimeout(filter.timeout);
                        }
                        filter.timeout = setTimeout(() => {
                            filter.timeout = null;
                            filter.sendAjax();
                            $input_submit.parent().addClass('is-filter-active');
                        }, 500);
                    });

                    const $input_submit = filter.form.find('button[type="submit"]');
                    $input_submit.unbind('click');
                    $input_submit.not('[data-ajax="false"]').click((e) => {
                        e.preventDefault();
                        filter.sendAjax();
                        $input_submit.parent().addClass('is-filter-active');
                    });

                    const $radio = filter.form.find('input[type="radio"]');
                    $radio.unbind('change');
                    $radio.not('[data-ajax="false"]').change((e) => {
                        e.preventDefault();
                        filter.sendAjax();
                    });

                    const $input_checkbox = filter.form.find('input[type="checkbox"]');
                    $input_checkbox.unbind('change');
                    $input_checkbox.not('[data-ajax="false"]').change((e) => {
                        e.preventDefault();
                        filter.sendAjax();
                    });

                    const $select = filter.form.find('select');
                    $select.unbind('change');
                    $select.not('[data-ajax="false"]').change((e) => {
                        e.preventDefault();
                        filter.sendAjax();
                    });

                    $select.filter('[data-target="input"]').change((e) => {
                        const $currentItem = $(e.target);
                        const $inputTarget = filter.form.find(`input.${$currentItem.data('target-name')}`);
                        if ($inputTarget) {
                            const $selectedOption = $currentItem.find('option:selected');
                            $inputTarget.val($selectedOption.val());
                            $inputTarget.data('push-url', $selectedOption.data('term-url'));

                            filter.sendAjax();
                        }
                    });

                    $select.filter('[data-target="ul"]').change((e) => {
                        const $currentItem = $(e.target);
                        filter.form
                            .find(`ul.${$currentItem.data('target-name')}`)
                            .find(`li a[data-term-slug="${$currentItem.find('option:selected').val()}"]`)
                            .trigger('click');
                        filter.sendAjax();
                    });

                    const $list = filter.form.find('ul[data-ajax-push-url="true"]').first();
                    $list.unbind('change');
                    $list.find('li a').click((e) => {
                        e.preventDefault();
                        $list.find('li a').removeClass('active_term');
                        const $activeTerm = $(e.target);
                        $activeTerm.addClass('active_term');

                        const $inputTarget = filter.form.find(`input.${$list.data('target-name')}`);
                        if ($inputTarget) {
                            $inputTarget.val($activeTerm.data('term-slug'));
                            $inputTarget.data('push-url', $activeTerm.attr('href'));
                            filter.form.find(`select.${$list.data('target-name')} option[value="${$activeTerm.data('term-slug')}"]`).prop('selected', true);

                            filter.sendAjax();
                        }
                    });

                    filter.form.unbind('keydown');
                    filter.form.on('ds_trigger_browser_button_used', (event) => {
                        event.preventDefault();
                        filter.sendAjax(0, false, false, true);
                    });
                },
                sendAjax(page = 1, push_state = true, push_page_num = false, browser_button_used = false) {
                    if (filter.doing_ajax !== null) {
                        filter.doing_ajax.abort();
                        filter.doing_ajax = null;
                        filter.module.find('.loader').remove();
                    }

                    const data = {
                        action: filter.action,
                        query: {
                            post_type: filter.query.post_type,
                            posts_per_page: filter.query.posts_per_page,
                            paged: page,
                        },
                        main_taxonomy: filter.query.main_taxonomy,
                        secondary_taxonomy: filter.query.secondary_taxonomy,
                        component: filter.component_styles,
                        device: $(window).width() <= 768 ? 'mobile' : 'desktop',
                        browser_button_used,
                        pagination: 'standard',
                    };

                    if (filter.moreBtn) {
                        data.pagination = 'ajax';
                    }

                    if (filter.form) {
                        data.form = filter.form.serialize();
                    }

                    if (filter.sort) {
                        data.query.orderby = filter.sort.val();
                    }

                    if (push_state && !filter.first_load) {
                        filter.buildURL(data.query.paged, push_page_num);
                    }

                    filter.doing_ajax = $.ajax({
                        url: filter.ajax_url,
                        type: 'POST',
                        data,
                        // eslint-disable-next-line no-unused-vars
                        beforeSend(xhr) {
                            filter.module.append(filter.preloader);
                        },
                        // eslint-disable-next-line no-shadow
                        success(data) {
                            if (data) {
                                if (data.posts) {
                                    if (filter.moreBtn) {
                                        if (data.page <= 1) filter.results.html('');

                                        filter.results.append(data.posts);
                                    }

                                    if (filter.pagination) {
                                        filter.results.html(data.posts);
                                    }
                                }

                                if (data.fragments) {
                                    Object.keys(data.fragments).forEach((key) => {
                                        jQuery(key).html(data.fragments[key]);
                                    });
                                }

                                if (data.max_pages === data.page && filter.moreBtn) {
                                    filter.moreBtn.hide();
                                    filter.moreBtn.attr('data-page', data.page);
                                } else if (filter.moreBtn) {
                                    filter.moreBtn.show();
                                    filter.moreBtn.attr('data-page', data.page);
                                }

                                filter.module.find('.loader').remove();
                            }

                            if (filter.pagination) {
                                filter.paginate(); // requires to re-init pagination because html was re-build
                            }
                            filter.doing_ajax = null;
                        },
                    });
                },
                buildURL(paged = 1, push_page_num = false) {
                    const url_parse_side = window.location.href.split('?');
                    const url = new URL(url_parse_side[0]);
                    const oldUrl = new URL(window.history.state && window.history.state.path ? window.history.state.path : window.location.href);
                    let push_state = false;

                    const inputPushUrl = filter.form.find('input[data-push-url]').first().data('push-url') ?? '';
                    if (inputPushUrl !== '') {
                        push_state = true;
                        url.href = inputPushUrl;
                    }

                    if (push_page_num) {
                        url.searchParams.set('page_num', isNaN(parseInt(filter.query.page)) ? 1 : parseInt(filter.query.page));
                        push_state = true;
                    } else {
                        url.searchParams.delete('page_num');
                    }

                    filter.form.find('input[type=text]:not([data-ajax="false"])').each(function () {
                        push_state = true;
                        if (jQuery(this).val().length > 0) {
                            url.searchParams.set(jQuery(this).attr('name'), jQuery(this).val());
                        }
                    });

                    filter.form.find('input[type=radio]:not([data-ajax="false"]):checked').each(function () {
                        push_state = true;
                        if (jQuery(this).val().length > 0) {
                            url.searchParams.set(jQuery(this).attr('name'), jQuery(this).val());
                        }
                    });

                    filter.form.find('input[type=checkbox]:not([data-ajax="false"]):checked').each(function () {
                        push_state = true;
                        url.searchParams.append(jQuery(this).attr('name'), jQuery(this).val());
                    });

                    filter.form.find('select:not([data-ajax="false"])').each(function () {
                        push_state = true;
                        if (jQuery(this).find('option:selected').val().length > 0) {
                            url.searchParams.set(jQuery(this).attr('name'), jQuery(this).find('option:selected').val());
                        }
                    });

                    filter.sort.each(function () {
                        push_state = true;
                        if (jQuery(this).find('option:selected').val().length > 0) {
                            url.searchParams.set(jQuery(this).attr('name'), jQuery(this).find('option:selected').val());
                        }
                    });

                    const decoded_url = decodeURIComponent(url);
                    if (push_state && (oldUrl.searchParams.toString() !== url.searchParams.toString() || oldUrl.href !== url.href)) {
                        window.history.pushState(
                            {
                                path: decoded_url,
                                ds_trigger_filter: true,
                                paged,
                            },
                            null,
                            decoded_url,
                        );
                    }
                },
                parseURL() {
                    // TODO move parse URL code here
                },
                setComponentStyles() {
                    const compClass = filter.module.data('class');
                    if (compClass) {
                        filter.component_styles.class = compClass;
                    }

                    const compStyles = filter.module.data('styles');
                    if (compStyles) {
                        filter.component_styles.styles = compStyles;
                    }

                    const compImage = filter.module.data('image');
                    if (compImage) {
                        filter.component_styles.image = compImage;
                    }
                },
            };

            filter.init(module);
        };

        const doInit = () => {
            $('.js-ajax-block').each((i) => {
                DSInitFilter($('.js-ajax-block')[i]);
            });
        };

        doInit();

        addEventListener('popstate', () => {
            $('.js-ajax-block').each((i, item) => {
                reInitFilter(item);
            });
        });
    })(jQuery);
};

/**
 * Reinitializes the filter based on the current URL parameters
 *
 * @param {string} filter - The filter element or selector
 */
const reInitFilter = (filter) => {
    const parsedUrl = window.location.href.split('?');
    const currentUrl = new URL(window.location.href.toString());
    const cleanedUrl = new URL(parsedUrl[0]);

    const params = currentUrl.searchParams;
    let triggerChange = false;
    const form = $(filter).find('form[data-form="ajax"]');

    form.find('input[type=text]:not([data-ajax="false"])').each(function (index, elem) {
        const $this = $(elem);
        $this.val(params.get($this.attr('name')) ?? '');
        triggerChange = true;
    });

    form.find('select:not([data-ajax="false"])').each(function (index, elem) {
        const $this = $(elem);
        const value = params.get($this.attr('name')) ?? '';
        if (value !== '') {
            $this.find(`option[value=${value}]`).prop('selected', true);
        } else {
            $this.find('option:eq(0)').prop('selected', true);
        }
        triggerChange = true;
    });

    form.find('ul[data-ajax-push-url="true"]:first li a').each(function (index, elem) {
        const $this = $(elem);
        if ($this.attr('href') === cleanedUrl.href && !$this.hasClass('active_term')) {
            $this.trigger('click');
            triggerChange = true;
        }
    });

    form.find('select[data-target="input"]').each(function (index, elem) {
        const $this = $(elem);
        const selectedOption = $this.find('option:selected');
        const $inputTarget = form.find(`input.${$this.data('target-name')}`);

        if (selectedOption.data('term-url') !== cleanedUrl.href) {
            $this.find(`option[data-term-url="${cleanedUrl.href}"]`).prop('selected', true);

            const $newSelectedOption = $this.find('option:selected');
            $inputTarget.val($newSelectedOption.val());
            $inputTarget.data('push-url', $newSelectedOption.data('term-url'));
            triggerChange = true;
        }
    });

    if (triggerChange) {
        form.trigger('ds_trigger_browser_button_used');
    }
};

export { dst_LoadMoreBlog };
