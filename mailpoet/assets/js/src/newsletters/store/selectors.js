const EMPTY_ARRAY = [];
const EMPTY_OBJECT = {};


export const getStandardNewsletters = (state) => {
	  return state.newsletters.standard || EMPTY_ARRAY
}

export const getMeta = (state) => {
	return state.meta || EMPTY_OBJECT
}

export const getStandardNewsletterLoading = (state) => {
	  return state.isLoading.standard || false
}
