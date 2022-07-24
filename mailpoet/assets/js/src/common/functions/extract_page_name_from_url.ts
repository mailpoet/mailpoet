export const extractPageNameFromUrl = () => {
  const searchParam = new URLSearchParams(window.location.search);
  const searchParamPage = searchParam.get('page') || '';
  const mailpoetPageName = searchParamPage.replace('mailpoet-', '');
  const pageNameFromUrl = mailpoetPageName || searchParamPage || '';
  return pageNameFromUrl;
};
