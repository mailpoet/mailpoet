import { startStandardRequest, receiveStandardNewsletters, receiveStandardSegments, finishStandardRequest, receiveError } from './actions';

function extractStandardNewsletters(data) {
  return data.filter((item) => item.type == 'standard').map((item) => {
        return {
        ...item,
        segment_ids: item.segments.map((segment) => segment.id),
        id: parseInt(item.id, 10)
        }
  });
}

function extractStandardSegments(data){
  let segments = [];
  data.filter((item) => item.type == 'standard').map((item) => {
      item.segments.map((segment) => {
          if(!segments.some((s) => s.id === segment.id)){
              segments.push(segment);
          }
      });
  });
  return segments;
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

      let standard_segments = extractStandardSegments(response.data);
      dispatch(receiveStandardSegments(standard_segments));

      dispatch(finishStandardRequest()); 
    } else {
      dispatch(receiveError("Invalid response"));
    }
  } catch (res) {
    dispatch(receiveError(res.errors));
    dispatch(finishStandardRequest()); 
  } 
};