import {
  Panel,
  PanelBody,
  PanelRow,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import jQuery from 'jquery';
import { Selection } from '../../../form/fields/selection';
import { GetValueCallback, SetValueCallback } from './types';

type Post = {
  id: number;
};

type Props = {
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
  priceDecimalSeparator: string;
};

type State = {
  minimumAmount: string;
  maximumAmount: string;
  individualUse: boolean;
  excludeSaleItems: boolean;
  productIds: Post[];
  excludedProductIds: Post[];
  productCategoryIds: Post[];
  excludedProductCategoryIds: Post[];
  emailRestrictions: string;
};

class UsageRestriction extends Component<Props, State> {
  private readonly getValueCallback: GetValueCallback;

  private readonly setValueCallback: SetValueCallback;

  private readonly priceDecimalSeparator: string;

  constructor(props: Props) {
    super(props);
    this.getValueCallback = props.getValueCallback;
    this.setValueCallback = props.setValueCallback;
    this.priceDecimalSeparator = props.priceDecimalSeparator;

    this.state = {
      minimumAmount: this.getValueCallback('minimumAmount') as string,
      maximumAmount: this.getValueCallback('maximumAmount') as string,
      individualUse: this.getValueCallback('individualUse') as boolean,
      excludeSaleItems: this.getValueCallback('excludeSaleItems') as boolean,
      productIds: this.getValueCallback('productIds').toJSON() as Post[],
      excludedProductIds: this.getValueCallback(
        'excludedProductIds',
      ).toJSON() as Post[],
      productCategoryIds: this.getValueCallback(
        'productCategoryIds',
      ).toJSON() as Post[],
      excludedProductCategoryIds: this.getValueCallback(
        'excludedProductCategoryIds',
      ).toJSON() as Post[],
      emailRestrictions: this.getValueCallback('emailRestrictions') as string,
    };
  }

  public handleSelection = (e): void => {
    const model = this.getValueCallback(e.target.name as string);
    model.reset(e.target.value.map((id) => ({ id })));
    this.setValueCallback(e.target.name as string, model);
    const newState = {};
    newState[e.target.name] = e.target.value;
    this.setState(newState);
  };

  render() {
    const productsField = {
      forceSelect2: true,
      endpoint: 'products',
      resetSelect2OnUpdate: true,
      multiple: true,
      placeholder: __('Search for a product…', 'mailpoet'),
    };
    const productCategoriesField = {
      forceSelect2: true,
      endpoint: 'product_categories',
      resetSelect2OnUpdate: true,
      multiple: true,
    };

    return (
      <Panel>
        <PanelBody
          title={__('Usage restriction', 'mailpoet')}
          className="mailpoet-coupon-block-usage-restriction"
          initialOpen={false}
        >
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_minimum_amount"
              label={__('Minimum spend', 'mailpoet')}
              value={this.state.minimumAmount}
              placeholder={__('No minimum', 'mailpoet')}
              onChange={(minimumAmount) => {
                this.setState({ minimumAmount });
                if (
                  jQuery('.mailpoet_field_coupon_minimum_amount input')
                    .parsley()
                    .isValid()
                ) {
                  this.setValueCallback('minimumAmount', minimumAmount);
                }
              }}
              pattern={`[0-9]+([${this.priceDecimalSeparator}][0-9]+)?`}
              data-parsley-validate
              data-parsley-trigger="input"
              data-parsley-validation-threshold="1"
              data-parsley-error-message={__(
                'Please enter a value with one monetary decimal point (%s) without thousand separators and currency symbols.',
                'mailpoet',
              ).replace('%s', this.priceDecimalSeparator)}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_maximum_amount"
              label={__('Maximum spend', 'mailpoet')}
              value={this.state.maximumAmount}
              placeholder={__('No maximum', 'mailpoet')}
              onChange={(maximumAmount) => {
                this.setState({ maximumAmount });
                if (
                  jQuery('.mailpoet_field_coupon_maximum_amount input')
                    .parsley()
                    .isValid()
                ) {
                  this.setValueCallback('maximumAmount', maximumAmount);
                }
              }}
              pattern={`[0-9]+([${this.priceDecimalSeparator}][0-9]+)?`}
              data-parsley-validate
              data-parsley-trigger="input"
              data-parsley-validation-threshold="1"
              data-parsley-error-message={__(
                'Please enter a value with one monetary decimal point (%s) without thousand separators and currency symbols.',
                'mailpoet',
              ).replace('%s', this.priceDecimalSeparator)}
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              checked={this.state.individualUse}
              label={__('Individual use only', 'mailpoet')}
              onChange={(individualUse) => {
                this.setValueCallback('individualUse', individualUse);
                this.setState({ individualUse });
              }}
              help={__(
                'Coupon cannot be used in conjunction with other coupons.',
                'mailpoet',
              )}
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              checked={this.state.excludeSaleItems}
              label={__('Exclude sale items', 'mailpoet')}
              onChange={(excludeSaleItems) => {
                this.setValueCallback('excludeSaleItems', excludeSaleItems);
                this.setState({ excludeSaleItems });
              }}
              help={__('Coupon does not apply to items on sale.', 'mailpoet')}
            />
          </PanelRow>
          <PanelRow>
            <label htmlFor="productIds">{__('Products', 'mailpoet')}</label>
            <Selection
              field={{
                ...productsField,
                name: 'productIds',
                selected: () =>
                  this.state.productIds.map((product: Post) => product.id),
              }}
              onValueChange={this.handleSelection}
            />
          </PanelRow>
          <PanelRow>
            <label htmlFor="excludedProductIds">
              {__('Excluded products', 'mailpoet')}
            </label>
            <Selection
              field={{
                ...productsField,
                name: 'excludedProductIds',
                selected: () =>
                  this.state.excludedProductIds.map(
                    (product: Post) => product.id,
                  ),
              }}
              onValueChange={this.handleSelection}
            />
          </PanelRow>
          <PanelRow>
            <label htmlFor="productCategoriesIds">
              {__('Product categories', 'mailpoet')}
            </label>
            <Selection
              field={{
                ...productCategoriesField,
                name: 'productCategoryIds',
                placeholder: __('Any category', 'mailpoet'),
                selected: () =>
                  this.state.productCategoryIds.map(
                    (category: Post) => category.id,
                  ),
              }}
              onValueChange={this.handleSelection}
            />
          </PanelRow>
          <PanelRow>
            <label htmlFor="excludedProductCategoryIds">
              {__('Exclude product categories', 'mailpoet')}
            </label>
            <Selection
              field={{
                ...productCategoriesField,
                name: 'excludedProductCategoryIds',
                placeholder: __('No categories', 'mailpoet'),
                selected: () =>
                  this.state.excludedProductCategoryIds.map(
                    (category: Post) => category.id,
                  ),
              }}
              onValueChange={this.handleSelection}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_email_restrictions"
              label={__('Allowed emails', 'mailpoet')}
              value={this.state.emailRestrictions}
              placeholder={__('No restrictions', 'mailpoet')}
              onChange={(emailRestrictions) => {
                this.setState({ emailRestrictions });
                if (
                  jQuery('.mailpoet_field_coupon_email_restrictions input')
                    .parsley()
                    .isValid()
                ) {
                  this.setValueCallback('emailRestrictions', emailRestrictions);
                }
              }}
              type="text"
              pattern="/^([\w\d._\-#\*])+@([\w\d._\-#\*]+[.][\w\d._\-#\*]+)+(,([\w\d._\-#\*])+@([\w\d._\-#\*]+[.][\w\d._\-#\*]+))*$/"
              data-parsley-validate
              data-parsley-validation-threshold="1"
              data-parsley-trigger="input"
              data-parsley-error-message={__(
                'Separate email addresses with commas. You can also use an asterisk (*) to match parts of an email. For example "*@gmail.com" would match all gmail addresses.',
                'mailpoet',
              )}
            />
          </PanelRow>
        </PanelBody>
      </Panel>
    );
  }
}

export { UsageRestriction };
