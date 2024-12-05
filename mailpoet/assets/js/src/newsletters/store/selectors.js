const EMPTY_ARRAY = [];


export const getNewsletterRows = (state) => {
	  return state.newsletterStandardRows || EMPTY_ARRAY
}

export const getNewsletterLoading = (state) => {
	  return state.isLoading || false
}