import MailPoet from 'mailpoet';
import _ from 'lodash';

interface FormItem {
  segmentType: string;
}

interface WordpressRoleFormItem extends FormItem {
  wordpressRole?: string;
}

interface WooCommerceFormItem extends FormItem {
  action?: string;
  category_id?: string;
  product_id?: string;
}

interface EmailFormItem extends FormItem {
  action?: string;
  newsletter_id?: string;
  link_id?: string;
}

type AnyFormItem = WordpressRoleFormItem | WooCommerceFormItem | EmailFormItem;

interface Result {
  count: number;
  errors: string[];
}

let previousFormItem: AnyFormItem | undefined;

let previousResult: Result | undefined;

// Names of keys from interface FormItem
const allowedItemKeys: string[] = ['wordpressRole', 'segmentType', 'action', 'newsletter_id', 'category_id', 'product_id', 'link_id'];

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
