import {
  Panel,
  PanelRow,
  PanelBody,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import Backbone from 'backbone';
import { Component } from '@wordpress/element';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';

type Props = {
  availableDiscountTypes: SelectControl.Option[];
  getValueCallback: (name: string) => string | boolean | Backbone.Collection;
  setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;
};

type State = {
  amount: string;
  amountMax: string;
  discountType: string;
  expiryDay: string;
  freeShipping: boolean;
};

class General extends Component<Props, State> {
  private readonly availableDiscountTypes: SelectControl.Option[];

  private readonly getValueCallback: (
    name: string,
  ) => string | boolean | Backbone.Collection;

  private readonly setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;

  constructor(props: Props) {
    super(props);
    this.availableDiscountTypes = props.availableDiscountTypes;
    this.getValueCallback = props.getValueCallback;
    this.setValueCallback = props.setValueCallback;
    this.state = {
      amount: this.getValueCallback('amount') as string,
      amountMax: this.getValueCallback('amountMax') as string,
      discountType: this.getValueCallback('discountType') as string,
      expiryDay: this.getValueCallback('expiryDay') as string,
      freeShipping: this.getValueCallback('freeShipping') as boolean,
    };

    this.discountTypeChange = this.discountTypeChange.bind(this);
  }

  public discountTypeChange = (discountType: string) => {
    const $amountField = jQuery('.mailpoet_field_coupon_amount input');
    $amountField.parsley().destroy();

    let amountMax = '';
    if (discountType.includes('percent')) {
      amountMax = '100';
    } else {
      amountMax = '1000000';
    }

    $amountField.prop('max', amountMax);
    $amountField.parsley().validate();

    this.setState({
      amountMax,
      discountType,
    });
    this.setValueCallback('amountMax', amountMax);
    this.setValueCallback('discountType', discountType);
  };

  render() {
    return (
      <Panel>
        <PanelBody title={MailPoet.I18n.t('generalSettings')}>
          <PanelRow>
            <SelectControl
              className="mailpoet_field_coupon_discount_type"
              label={MailPoet.I18n.t('discountType')}
              onChange={this.discountTypeChange}
              options={this.availableDiscountTypes}
              value={this.state.discountType}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_amount"
              label={MailPoet.I18n.t('couponAmount')}
              onChange={(amount: string) => {
                this.setState({ amount });
                // the model is changed only when the value is valid
                if (
                  jQuery('.mailpoet_field_coupon_amount input')
                    .parsley()
                    .isValid()
                ) {
                  this.setValueCallback('amount', amount);
                }
              }}
              type="number"
              min="0"
              max={this.state.amountMax}
              value={this.state.amount}
              data-parsley-validate
              data-parsley-required
              data-parsley-validation-threshold="0"
              data-parsley-trigger="input"
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_expiry_day"
              label={MailPoet.I18n.t('expireIn')}
              onChange={(expiryDay) => {
                this.setState({ expiryDay });
                if (
                  jQuery('.mailpoet_field_coupon_expiry_day input')
                    .parsley()
                    .isValid()
                ) {
                  this.setValueCallback('expiryDay', expiryDay);
                }
              }}
              min="0"
              type="number"
              value={this.state.expiryDay}
              data-parsley-required
              data-parsley-validation-threshold="0"
              data-parsley-trigger="input"
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              className="mailpoet_field_coupon_free_shipping"
              checked={this.state.freeShipping}
              label={MailPoet.I18n.t('freeShipping')}
              onChange={(freeShipping) => {
                this.setValueCallback('freeShipping', freeShipping);
                this.setState({ freeShipping });
              }}
            />
          </PanelRow>
        </PanelBody>
      </Panel>
    );
  }
}

export { General };
