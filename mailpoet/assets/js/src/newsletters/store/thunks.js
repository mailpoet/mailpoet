import { receiveNewsletters, receiveError, fetchNewslettersRequest, fetchNewslettersSuccess, fetchNewslettersFailure } from './actions';

export const loadNewsletters = () => async ({ select, dispatch }) => {
  let data = {
    data: [],
    meta: { count: 0, groups: [], filters: { segment: [] } },
  };

  dispatch(fetchNewslettersRequest()); 

  try {
    const response = await MailPoet.Ajax.post({
      api_version: 'v1',
      endpoint: 'newsletters',
      action: 'listing',
    });
    const keys = Object.keys(response);
    if (keys.includes('data') && keys.includes('meta')) {
      data = response;
      dispatch(receiveNewsletters(data));
      dispatch(fetchNewslettersSuccess());
    } else {
      dispatch(receiveError("Invalid response"));
    }
  } catch (res) {
    dispatch(receiveError(res.errors));
    dispatch(fetchNewslettersFailure()); 
  } 
  return data;
};