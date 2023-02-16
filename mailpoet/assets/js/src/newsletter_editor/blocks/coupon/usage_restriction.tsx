import {
  Panel,
  PanelRow,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
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
      placeholder: MailPoet.I18n.t('searchForProduct'),
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
          title={MailPoet.I18n.t('usageRestriction')}
          className="mailpoet-coupon-block-usage-restriction"
          initialOpen={false}
        >
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_minimum_amount"
              label={MailPoet.I18n.t('minimumSpend')}
              value={this.state.minimumAmount}
              placeholder={MailPoet.I18n.t('noMinimum')}
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
              data-parsley-error-message={MailPoet.I18n.t(
                'couponMinAndMaxAmountFieldsErrorMessage',
              ).replace('%s', this.priceDecimalSeparator)}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_maximum_amount"
              label={MailPoet.I18n.t('maximumSpend')}
              value={this.state.maximumAmount}
              placeholder={MailPoet.I18n.t('noMaximum')}
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
              data-parsley-error-message={MailPoet.I18n.t(
                'couponMinAndMaxAmountFieldsErrorMessage',
              ).replace('%s', this.priceDecimalSeparator)}
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              checked={this.state.individualUse}
              label={MailPoet.I18n.t('individualUseOnly')}
              onChange={(individualUse) => {
                this.setValueCallback('individualUse', individualUse);
                this.setState({ individualUse });
              }}
              help={MailPoet.I18n.t('individualUseHelp')}
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              checked={this.state.excludeSaleItems}
              label={MailPoet.I18n.t('excludeSaleItems')}
              onChange={(excludeSaleItems) => {
                this.setValueCallback('excludeSaleItems', excludeSaleItems);
                this.setState({ excludeSaleItems });
              }}
              help={MailPoet.I18n.t('excludeSaleItemsHelp')}
            />
          </PanelRow>
          <PanelRow>
            <label htmlFor="productIds">{MailPoet.I18n.t('products')}</label>
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
              {MailPoet.I18n.t('excludedProducts')}
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
              {MailPoet.I18n.t('productCategories')}
            </label>
            <Selection
              field={{
                ...productCategoriesField,
                name: 'productCategoryIds',
                placeholder: MailPoet.I18n.t('anyCategory'),
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
              {MailPoet.I18n.t('excludeProductCategories')}
            </label>
            <Selection
              field={{
                ...productCategoriesField,
                name: 'excludedProductCategoryIds',
                placeholder: MailPoet.I18n.t('noCategories'),
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
              label={MailPoet.I18n.t('allowedEmails')}
              value={this.state.emailRestrictions}
              placeholder={MailPoet.I18n.t('noRestrictions')}
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
              data-parsley-error-message={MailPoet.I18n.t(
                'emailRestrictionsFieldsErrorMessage',
              )}
            />
          </PanelRow>
        </PanelBody>
      </Panel>
    );
  }
}

export { UsageRestriction };
