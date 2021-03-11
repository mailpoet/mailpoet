import MailPoet from 'mailpoet';
import _ from 'lodash';

export interface FormItem {
  wordpressRole: string | undefined;
  segmentType: string | undefined;
  action: string | undefined;
  newsletter_id: string | undefined;
  category_id: string | undefined;
  product_id: string | undefined;
}

export interface Result {
  count: number;
  errors: string[];
}

interface ApiVersionWindow extends Window {
  mailpoet_api_version: string;
}

declare let window: ApiVersionWindow;

class SubscribersCalculator {
  emailActions: string[] = ['opened', 'notOpened', 'clicked', 'notClicked'];

  wooCommerceActions: string[] = ['purchasedCategory', 'purchasedProduct'];

  // Names of keys from interface FormItem
  allowedItemKeys: string[] = ['wordpressRole', 'segmentType', 'action', 'newsletter_id', 'category_id', 'product_id'];

  lastFormItem: FormItem;

  lastResult: Result;

  validateInputData(formItem: FormItem): boolean {
    switch (formItem.segmentType) {
      case 'userRole':
        if (!formItem.wordpressRole) {
          return false;
        }
        return true;

      case 'email':
        if (!this.emailActions.includes(formItem.action) || !formItem.newsletter_id) {
          return false;
        }
        return true;

      case 'woocommerce':
        if (!this.wooCommerceActions.includes(formItem.action)) {
          return false;
        }
        if (formItem.action === 'purchasedCategory' && !formItem.category_id) {
          return false;
        }
        if (formItem.action === 'purchasedProduct' && !formItem.product_id) {
          return false;
        }
        return true;

      default: return false;
    }
  }

  loadCount(formItem: FormItem): Promise<Result | void> {
    if (!this.validateInputData(formItem)) {
      return Promise.resolve();
    }
    // We don't want to use properties like name and description
    const item = _.pick(formItem, this.allowedItemKeys);
    // When item is the same as in the previous call we return previous result
    if (_.isEqual(item, this.lastFormItem)) {
      return Promise.resolve(this.lastResult);
    }
    this.lastFormItem = { ...item } as FormItem;

    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'dynamic_segments',
      action: 'getCount',
      data: formItem,
      timeout: 30000, // 30 seconds
    })
      .then((response) => {
        const { data } = response;
        this.lastResult = {
          count: data.count,
          errors: undefined,
        };
        return this.lastResult;
      })
      .catch((response) => {
        const errors = response.errors.map((error) => error.message);
        return {
          count: undefined,
          errors,
        };
      });
  }
}

export default SubscribersCalculator;
