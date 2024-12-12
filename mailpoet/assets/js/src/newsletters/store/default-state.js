
export const defaultNewsletterState = {
        
    newsletters: {
        standard: [],
        postNotifications: [],
        reEngagements: [],
    },
    meta: {},
    isLoading: { standard: false, 
                 postNotification: false,
                 reEngagement: false,},
    errors: [],
    currentNewsletterType: "standard",
    mailpoet_newsletters_count: window.mailpoet_newsletters_count,
    wordpressRoles: window.wordpress_editable_roles_list,

};
