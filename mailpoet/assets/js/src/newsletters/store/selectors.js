const EMPTY_ARRAY = [];


export const getStandardNewsletters = (state) => {
	  return state.newsletters.standard || EMPTY_ARRAY
}

export const getStandardNewsletterLoading = (state) => {
	  return state.isLoading.standard || false
}

export const getStandardSegments = (state) => {
	  return state.newsletters.standardSegments || EMPTY_ARRAY
}