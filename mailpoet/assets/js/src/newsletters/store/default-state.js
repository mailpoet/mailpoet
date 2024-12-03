import { storeName } from './constants';


export const defaultNewsletterState = {
    newsletterListing: {
        data: [],
        meta: { count: 0, groups: [], filters: { segment: [] } },
    },
    errors: [],
    isFetching: false,
    mailpoet_newsletters_count: window.mailpoet_newsletters_count,
    wordpressRoles: window.wordpress_editable_roles_list,

};
