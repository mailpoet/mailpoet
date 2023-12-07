import {
  Panel,
  PanelBody,
  PanelRow,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import jQuery from 'jquery';
import { SelectControlProps } from '@wordpress/components/build-types/select-control/types';
import { GetValueCallback, SetValueCallback } from './types';

type Props = {
  availableDiscountTypes: SelectControlProps['options'];
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
};

type State = {
  amount: string;
  amountMax: string;
  discountType: string;
  expiryDay: string;
  freeShipping: boolean;
};

class General extends Component<Props, State> {
  private readonly availableDiscountTypes: SelectControlProps['options'];

  private readonly getValueCallback: GetValueCallback;

  private readonly setValueCallback: SetValueCallback;

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
        <PanelBody title={__('General settings', 'mailpoet')}>
          <PanelRow>
            <SelectControl
              label={__('Discount type', 'mailpoet')}
              onChange={this.discountTypeChange}
              options={this.availableDiscountTypes}
              value={this.state.discountType}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_amount"
              label={__('Coupon amount', 'mailpoet')}
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
              label={__('Expires in', 'mailpoet')}
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
              checked={this.state.freeShipping}
              label={__('Free shipping', 'mailpoet')}
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
