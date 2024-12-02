import { storeName } from './constants';


export const defaultNewsletterState = {
    list: [],
    errors: [],
    isFetching: false,
    mailpoet_newsletters_count: window.mailpoet_newsletters_count,
    wordpressRoles: window.wordpress_editable_roles_list,

};
