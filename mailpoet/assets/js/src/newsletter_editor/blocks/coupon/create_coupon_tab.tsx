import {
  Panel,
  PanelRow,
  PanelBody,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';

export type CreateCouponTabProps = {
  availableDiscountTypes: SelectControl.Option[];
  getValueCallback: (name: string) => string | boolean;
  setValueCallback: (name: string, value: string | boolean) => void;
};

export type CreateCouponTabState = {
  amount: string;
  amountMax: string;
  discountType: string;
  expiryDay: string;
  freeShipping: boolean;
};

class CreateCouponTab extends Component<
  CreateCouponTabProps,
  CreateCouponTabState
> {
  private readonly availableDiscountTypes: SelectControl.Option[];

  private readonly getValueCallback: (name: string) => string | boolean;

  private readonly setValueCallback: (
    name: string,
    value: string | boolean,
  ) => void;

  constructor(props: CreateCouponTabProps) {
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

  componentDidMount() {
    jQuery('.mailpoet_field_coupon_amount input').parsley().validate();
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
                this.setValueCallback('amount', amount);
                this.setState({ amount });
              }}
              type="number"
              min="0"
              max={this.state.amountMax}
              value={this.state.amount}
              data-parsley-validate
              data-parsley-required
              data-parsley-trigger="input"
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_expiry_day"
              label={MailPoet.I18n.t('expireIn')}
              onChange={(expiryDay) => {
                this.setValueCallback('expiryDay', expiryDay);
                this.setState({ expiryDay });
              }}
              min="0"
              type="number"
              value={this.state.expiryDay}
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

export { CreateCouponTab };
