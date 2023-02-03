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

type Props = {
  getValueCallback: (name: string) => string | boolean;
  setValueCallback: (name: string, value: string | boolean) => void;
  priceDecimalSeparator: string;
};

type State = {
  minimumAmount: string;
  maximumAmount: string;
  individualUse: boolean;
  excludeSaleItems: boolean;
};

class UsageRestriction extends Component<Props, State> {
  private readonly getValueCallback: (name: string) => string | boolean;

  private readonly setValueCallback: (
    name: string,
    value: string | boolean,
  ) => void;

  private priceDecimalSeparator: string;

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

  render() {
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
        </PanelBody>
      </Panel>
    );
  }
}

export { UsageRestriction };
