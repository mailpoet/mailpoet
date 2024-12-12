import { startStandardRequest, receiveStandardNewsletters, receiveStandardSegments, finishStandardRequest, receiveError, receiveMeta } from './actions';

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