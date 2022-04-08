import MailPoet from 'mailpoet';
import _ from 'lodash';

import { AnyFormItem, Segment, SegmentConnectTypes } from './types';

export interface Result {
  count: number;
  errors: string[];
}

export interface Filters {
  filters: AnyFormItem[];
  filters_connect: SegmentConnectTypes;
}

let previousFormItem: Filters | undefined;

let previousResult: Result | undefined;

function loadCount(formItem: Segment): PromiseLike<Result> {
  // We don't want to use properties like name and description
  const item = {
    filters: formItem.filters,
    filters_connect: formItem.filters_connect,
  };
  // When item is the same as in the previous call we return previous result
  if (_.isEqual(item, previousFormItem)) {
    return Promise.resolve(previousResult);
  }
  previousFormItem = {
    filters: [...formItem.filters],
    filters_connect: formItem.filters_connect,
  };

  return MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'dynamic_segments',
    action: 'getCount',
    data: formItem,
    timeout: 20000, // 20 seconds
  }).then((response) => {
    const { data } = response;
    previousResult = {
      count: data.count,
      errors: undefined,
    };
    return previousResult;
  });
}

export { loadCount };
