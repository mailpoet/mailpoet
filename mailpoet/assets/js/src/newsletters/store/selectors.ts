
import { NewsletterState, Meta } from './types';
import { NewsLetter } from '../../common/newsletter';


const EMPTY_ARRAY: NewsLetter[] = [];
const EMPTY_OBJECT: Meta = {} as Meta;

export const getStandardNewsletters = (state: NewsletterState): NewsLetter[] => {
    return state.newsletters.standard || EMPTY_ARRAY;
}

export const getMeta = (state: NewsletterState): Meta | {} => {
    return state.meta || EMPTY_OBJECT;
}

export const getStandardNewsletterLoading = (state: NewsletterState): boolean => {
    return state.isLoading.standard || false;
}