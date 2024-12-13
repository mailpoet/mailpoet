import { startStandardRequest, 
         receiveStandardNewsletters, 
         finishStandardRequest, 
         receiveError, 
         receiveMeta,
         startDuplicateRequest,
         receiveDuplicatedNewsletter,
         finishDuplicateRequest 
        } from './actions';

function extractStandardNewsletters(data) {
  return data.filter((item) => item.type == 'standard').map((item) => {
        return {
        ...item,
        id: parseInt(item.id, 10)
        }
  });
}




export const loadNewsletters = () => async ({ select, dispatch }) => {

  dispatch(startStandardRequest()); 

  try {
    const response = await MailPoet.Ajax.post({
      api_version: 'v1',
      endpoint: 'newsletters',
      action: 'listing',
      data: {
        params: { type: 'standard' },
      },
    });
    const keys = Object.keys(response);
    if (keys.includes('data') && keys.includes('meta')) {
      let standard_newsletters = extractStandardNewsletters(response.data);
      dispatch(receiveStandardNewsletters(standard_newsletters));
      dispatch(receiveMeta(response.meta))

      dispatch(finishStandardRequest()); 
    } else {
      dispatch(receiveError("Invalid response"));
    }
  } catch (res) {
    dispatch(receiveError(res.errors));
    dispatch(finishStandardRequest()); 
  } 
};

// We accept newsletterId as a parameter. That way, the UI calling this thunk 
// can pass the ID directly instead of relying on store state.
export const duplicateNewsletter = (newsletterId) => async ({ select, dispatch }) => {
  dispatch(startDuplicateRequest());
  try {
    const response = await MailPoet.Ajax.post({
      api_version: 'v1',
      endpoint: 'newsletters',
      action: 'duplicate',
      data: {
        id: newsletterId
      },
    });

    // Assuming the response returns the newly duplicated newsletter data
    if (response && response.data) {
      const { body, ...newsletterWithoutBody } = response.data;
      newsletterWithoutBody.queue = false
      newsletterWithoutBody.id = parseInt(newsletterWithoutBody.id, 10)
      newsletterWithoutBody.segments ??= [];

      dispatch(receiveDuplicatedNewsletter(newsletterWithoutBody));
    } else {
      dispatch(receiveError('Invalid response from server'));
    }

    dispatch(finishDuplicateRequest());
  } catch (err) {
    // err likely has an errors property similar to your loadNewsletters code
    const errorMessage = (err && err.errors) ? err.errors : 'An error occurred';
    dispatch(receiveError(errorMessage));
    dispatch(finishDuplicateRequest());
  }
};