


export const loadNewsletters = () => async ({ select, dispatch }) => {
    let data = {
      data: [],
      meta: { count: 0, groups: [], filters: { segment: [] } },
    };
  
    try {
  
      const response = await MailPoet.Ajax.post({
        api_version: 'v1',
        endpoint: 'newsletters',
        action: 'listing',
      });
      const keys = Object.keys(response);
      if (keys.includes('data') && keys.includes('meta')) {
        data = response;
      }
    } catch (res) {
      if (res === 'abort') {
        return { type: 'NOOP' };
      }
      if (isErrorResponse(res)) {
        MailPoet.Notice.showApiErrorNotice(res);
      }
    }
    dispatch.receiveNewsletters(data);
  
    return data;
  }
  