
import { NewsletterState } from './types';


export const defaultNewsletterState: NewsletterState = {
        
    newsletters: {
        standard: [],
        postNotifications: [],
        reEngagements: [],
    },
    meta: {},
    isLoading: { standard: false, 
                 postNotification: false,
                 reEngagement: false,
                 duplication: false,
                },
    errors: [],
    currentNewsletterType: "standard",
};
