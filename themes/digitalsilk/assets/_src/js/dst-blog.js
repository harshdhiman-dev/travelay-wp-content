/**
 * Blog JS imports and functions
 */

import { dst_FlexTabs } from './components/dst_FlexTabs';
import { dst_LoadMoreBlog } from './blog/dst_BlogFilter';

document.addEventListener('DOMContentLoaded', () => {
    dst_FlexTabs();
    // Move to dst-blog.js if not using Content block with load more
    if (document.querySelector('.js-ajax-block')) {
        dst_LoadMoreBlog();
    }
});
