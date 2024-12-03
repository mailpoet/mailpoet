export const getNewsletterData = (state) => {
  return state.newsletterListing?.data || [];
};