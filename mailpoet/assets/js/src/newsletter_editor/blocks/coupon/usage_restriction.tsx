import {
  Panel,
  PanelRow,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import Backbone from 'backbone';
import { Component } from '@wordpress/element';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
import { Selection } from '../../../form/fields/selection';

type Post = {
  id: number;
};

type Props = {
  getValueCallback: (name: string) => string | boolean | Backbone.Collection;
  setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;
  priceDecimalSeparator: string;
};

type State = {
  minimumAmount: string;
  maximumAmount: string;
  individualUse: boolean;
  excludeSaleItems: boolean;
  productIds: Post[];
  excludedProductIds: Post[];
};

class UsageRestriction extends Component<Props, State> {
  private readonly getValueCallback: (
    name: string,
  ) => string | boolean | Backbone.Collection;

  private readonly setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;

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
    };
  }

  componentDidMount() {
    const $inputs = jQuery(
      '.mailpoet_field_coupon_minimum_amount input, .mailpoet_field_coupon_maximum_amount input',
    );
    if ($inputs.length) {
      $inputs.each((_index, input) => {
        jQuery(input).parsley().validate();
      });
    }
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

    return (
      <Panel>
        <PanelBody
          title={MailPoet.I18n.t('usageRestriction')}
          className="mailpoet-coupon-block-usage-restriction"
        >
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_minimum_amount"
              label={MailPoet.I18n.t('minimumSpend')}
              value={this.state.minimumAmount}
              placeholder={MailPoet.I18n.t('noMinimum')}
              onChange={(minimumAmount) => {
                this.setValueCallback('minimumAmount', minimumAmount);
                this.setState({ minimumAmount });
              }}
              pattern={`[0-9]+([${this.priceDecimalSeparator}][0-9]+)?`}
              data-parsley-validate
              data-parsley-trigger="input"
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
                this.setValueCallback('maximumAmount', maximumAmount);
                this.setState({ maximumAmount });
              }}
              pattern={`[0-9]+([${this.priceDecimalSeparator}][0-9]+)?`}
              data-parsley-validate
              data-parsley-trigger="input"
              data-parsley-error-message={MailPoet.I18n.t(
                'couponMinAndMaxAmountFieldsErrorMessage',
              ).replace('%s', this.priceDecimalSeparator)}
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              className="mailpoet_field_coupon_individual_use"
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
              className="mailpoet_field_coupon_exclude_sale_items"
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
        </PanelBody>
      </Panel>
    );
  }
}

export { UsageRestriction };
