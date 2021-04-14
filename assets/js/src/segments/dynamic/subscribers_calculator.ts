import MailPoet from 'mailpoet';
import _ from 'lodash';

import {
  AnyFormItem,
} from './types';

export interface Result {
  count: number;
  errors: string[];
}

let previousFormItem: AnyFormItem | undefined;

let previousResult: Result | undefined;

// Names of keys from interface FormItem
const allowedItemKeys: string[] = [
  'wordpressRole',
  'segmentType',
  'action',
  'newsletter_id',
  'category_id',
  'product_id',
  'link_id',
  'days',
  'opens',
  'operator',
];

function loadCount(formItem: AnyFormItem): Promise<Result | void> {
  // We don't want to use properties like name and description
  const item = _.pick(formItem, allowedItemKeys);
  // When item is the same as in the previous call we return previous result
  if (_.isEqual(item, previousFormItem)) {
    return Promise.resolve(previousResult);
  }
  previousFormItem = { ...item } as AnyFormItem;

  return MailPoet.Ajax.post({
    api_version: MailPoet.apiVersion,
    endpoint: 'dynamic_segments',
    action: 'getCount',
    data: formItem,
    timeout: 30000, // 30 seconds
  })
    .then((response) => {
      const { data } = response;
      previousResult = {
        count: data.count,
        errors: undefined,
      };
      return previousResult;
    });
}

export { loadCount };
