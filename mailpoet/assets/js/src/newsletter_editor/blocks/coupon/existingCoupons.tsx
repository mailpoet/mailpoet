import {
  Panel,
  PanelBody,
  PanelRow,
  SearchControl,
  SelectControl,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { GetValueCallback, SetValueCallback } from './types';

export type Coupon = {
  id: number;
  text: string;
  excerpt: string;
  discountType: string;
};

type Props = {
  availableDiscountTypes: SelectControl.Option[];
  availableCoupons: Coupon[];
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
};

type State = {
  couponSearch: string;
  couponFilterDiscountType: string;
  couponId: number;
};

class ExistingCoupons extends Component<Props, State> {
  private readonly availableDiscountTypes: SelectControl.Option[];

  private readonly availableCoupons: Coupon[];

  private readonly getValueCallback: GetValueCallback;

  private readonly setValueCallback: SetValueCallback;

  constructor(props: Props) {
    super(props);
    this.availableCoupons = props.availableCoupons;
    this.getValueCallback = props.getValueCallback;
    this.setValueCallback = props.setValueCallback;
    this.availableDiscountTypes = [
      {
        label: __('All types', 'mailpoet'),
        value: '',
      },
    ].concat(props.availableDiscountTypes);

    this.state = {
      couponSearch: '',
      couponFilterDiscountType: '',
      couponId: this.getValueCallback('couponId') as number,
    };
  }

  private filterCoupons = (): Coupon[] => {
    let coupons: Coupon[] = [];
    coupons = this.state.couponFilterDiscountType
      ? this.availableCoupons.filter(
          (coupon) =>
            coupon.discountType === this.state.couponFilterDiscountType,
        )
      : this.availableCoupons;
    if (this.state.couponSearch) {
      coupons = coupons.filter((coupon) =>
        coupon.text
          .toLowerCase()
          .includes(this.state.couponSearch.toLowerCase()),
      );
    }
    const allDiscountTypes = this.availableDiscountTypes
      .map((type) => type.value)
      .filter((type) => type !== '');
    // Ensure we only include coupons with a known discount type
    coupons = coupons.filter((coupon) =>
      allDiscountTypes.includes(coupon.discountType),
    );
    return coupons;
  };

  render() {
    return (
      <>
        <Panel>
          <PanelBody>
            <PanelRow>
              <SearchControl
                value={this.state.couponSearch}
                onChange={(couponSearch): void => {
                  this.setState({ couponSearch });
                }}
              />
            </PanelRow>
            <PanelRow>
              <SelectControl
                label={__('Discount type', 'mailpoet')}
                onChange={(couponFilterDiscountType) =>
                  this.setState({ couponFilterDiscountType })
                }
                options={this.availableDiscountTypes}
                value={this.state.couponFilterDiscountType}
              />
            </PanelRow>
          </PanelBody>
        </Panel>
        <Panel>
          <PanelBody className="mailpoet-coupon-block-existing-coupons">
            <PanelRow>
              {this.filterCoupons().length > 0 ? (
                this.filterCoupons()
                  .slice(0, 10)
                  .map((coupon) => {
                    const discountType = this.availableDiscountTypes.find(
                      (option) => option.value === coupon.discountType,
                    );
                    return (
                      <div
                        key={`coupon-${coupon.id}`}
                        className="mailpoet_coupon_block_coupon"
                      >
                        <input
                          id={`coupon-${coupon.id}`}
                          className="components-radio-control__input"
                          name="coupon"
                          type="radio"
                          value={coupon.id}
                          checked={coupon.id === this.state.couponId}
                          onChange={(event) => {
                            const couponId = Number(event.target.value);
                            this.setState({ couponId });
                            this.setValueCallback('couponId', couponId);
                            this.setValueCallback('code', coupon.text);
                          }}
                        />
                        <label htmlFor={`coupon-${coupon.id}`}>
                          {coupon.text}
                        </label>
                        <div className="discount_type">
                          {discountType.label}
                        </div>
                        {coupon.excerpt ? <div>{coupon.excerpt}</div> : null}
                      </div>
                    );
                  })
              ) : (
                <div className="mailpoet_coupon_block_coupon">
                  {__('No coupons found', 'mailpoet')}
                </div>
              )}
            </PanelRow>
          </PanelBody>
        </Panel>
      </>
    );
  }
}

export { ExistingCoupons };
