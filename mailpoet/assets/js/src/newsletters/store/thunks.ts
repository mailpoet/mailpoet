import { MailPoet } from 'mailpoet';
import { isErrorResponse } from '../../ajax';


import { 
  startStandardRequest, 
  receiveStandardNewsletters, 
  finishStandardRequest, 
  receiveError, 
  receiveMeta,
  startDuplicateRequest,
  receiveDuplicatedNewsletter,
  finishDuplicateRequest 
} from './actions';
import { NewsletterActions } from './types';

function extractStandardNewsletters(data: any[]): any[] {
  return data.filter((item) => item.type == 'standard').map((item) => {
        return {
        ...item,
        id: parseInt(item.id, 10)
        }
  });
}

export const loadNewsletters = () => async ({ dispatch }: { dispatch: (action: NewsletterActions) => void }): Promise<void> => {
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
  } catch (res: unknown) {
    if (res === 'abort') {
        dispatch(receiveError('aborted'));
      }

      if (isErrorResponse(res)) {
        MailPoet.Notice.showApiErrorNotice(res);
        dispatch(receiveError(res.errors[0].message));
    }
    
    dispatch(finishStandardRequest()); 
  } 
};

// We accept newsletterId as a parameter. That way, the UI calling this thunk 
// can pass the ID directly instead of relying on store state.
export const duplicateNewsletter = (newsletterId: number) => async ({ dispatch }: { dispatch: (action: NewsletterActions) => void }): Promise<void> => {
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
} catch (res: unknown) {
    if (res === 'abort') {
        dispatch(receiveError('aborted'));
      }

      if (isErrorResponse(res)) {
        MailPoet.Notice.showApiErrorNotice(res);
        dispatch(receiveError(res.errors[0].message));
    }
    
    dispatch(finishStandardRequest()); 
  }
};