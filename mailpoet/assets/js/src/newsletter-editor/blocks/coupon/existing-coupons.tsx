import {
  Button,
  Panel,
  PanelBody,
  PanelRow,
  SearchControl,
  SelectControl,
  Spinner,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { uniqBy, debounce } from 'lodash';
import { SelectControlProps } from '@wordpress/components/build-types/select-control/types';
import { MailPoet } from '../../../mailpoet';
import { GetValueCallback, SetValueCallback } from './types';

const COUPONS_PER_PAGE = 1000;

export type Coupon = {
  id: number;
  text: string;
  excerpt: string;
  discountType: string;
};

type Props = {
  availableDiscountTypes: SelectControlProps['options'];
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
};

type State = {
  couponSearch: string;
  couponFilterDiscountType: string;
  couponId: number;
  availableCoupons: Coupon[];
  loadingInitial: boolean;
  loadingMore: boolean;
  pageNumber: number;
  moreCouponsAvailable: boolean;
};

class ExistingCoupons extends Component<Props, State> {
  private readonly availableDiscountTypes: SelectControlProps['options'];

  private readonly getValueCallback: GetValueCallback;

  private readonly setValueCallback: SetValueCallback;

  loadCouponsDebounced = debounce(() => {
    this.loadCoupons();
  }, 300);

  constructor(props: Props) {
    super(props);
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
      availableCoupons: [],
      loadingInitial: false,
      loadingMore: false,
      pageNumber: 1,
      moreCouponsAvailable: true,
    };
  }

  componentDidMount() {
    this.loadCoupons();
  }

  handleSearchInputChange = (couponSearch: string) => {
    this.setState({ couponSearch }, () => this.loadCouponsDebounced());
  };

  private fetchCoupons(resetCoupons: boolean) {
    const loadingKey = resetCoupons ? 'loadingInitial' : 'loadingMore';
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'coupons',
      action: 'getCoupons',
      data: {
        page_size: COUPONS_PER_PAGE,
        page_number: this.state.pageNumber,
        discount_type: this.state.couponFilterDiscountType,
        search: this.state.couponSearch,
        include_coupon_ids: [this.state.couponId],
      },
    })
      .then((response) => {
        const newCoupons = response.data || [];
        let newAvailableCoupons = [];
        if (!resetCoupons) {
          const { availableCoupons } = this.state;
          newAvailableCoupons = uniqBy(
            [...availableCoupons, ...newCoupons],
            'id',
          );
        } else {
          newAvailableCoupons = newCoupons;
        }

        this.setState((prevState) => ({
          ...prevState,
          availableCoupons: newAvailableCoupons,
          moreCouponsAvailable: newCoupons.length > 0,
          [loadingKey]: false,
        }));
      })
      .catch(() => {
        MailPoet.Notice.error(
          __('Loading coupons was not successful', 'mailpoet'),
          {
            scroll: true,
          },
        );
        this.setState((prevState) => ({
          ...prevState,
          [loadingKey]: false,
        }));
      });
  }

  private loadCoupons() {
    this.setState(
      {
        pageNumber: 1,
        loadingInitial: true,
      },
      () => this.fetchCoupons(true),
    );
  }

  private loadMoreCoupons() {
    const { pageNumber } = this.state;
    const newPageNumber = pageNumber + 1;
    this.setState(
      {
        pageNumber: newPageNumber,
        loadingMore: true,
      },
      () => this.fetchCoupons(false),
    );
  }

  render() {
    const {
      loadingInitial,
      loadingMore,
      moreCouponsAvailable,
      availableCoupons,
    } = this.state;

    return (
      <>
        <Panel>
          <PanelBody>
            <PanelRow>
              <SearchControl
                value={this.state.couponSearch}
                onChange={this.handleSearchInputChange}
              />
            </PanelRow>
            <PanelRow>
              <SelectControl
                label={__('Discount type', 'mailpoet')}
                onChange={(couponFilterDiscountType) => {
                  this.setState({ couponFilterDiscountType }, () =>
                    this.loadCoupons(),
                  );
                }}
                options={this.availableDiscountTypes}
                value={this.state.couponFilterDiscountType}
              />
            </PanelRow>
          </PanelBody>
        </Panel>
        <Panel>
          <PanelBody className="mailpoet-coupon-block-existing-coupons">
            <PanelRow>
              {loadingInitial ? (
                <div className="mailpoet_coupon_block_coupon">
                  <Spinner />
                </div>
              ) : null}
              {!loadingInitial && availableCoupons.length > 0
                ? availableCoupons.map((coupon) => {
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
                : null}
              {!loadingInitial && availableCoupons.length === 0 ? (
                <div className="mailpoet_coupon_block_coupon">
                  {__('No coupons found', 'mailpoet')}
                </div>
              ) : null}
              {!loadingMore && moreCouponsAvailable ? (
                <Button
                  variant="secondary"
                  onClick={() => this.loadMoreCoupons()}
                >
                  {__('Load more', 'mailpoet')}
                </Button>
              ) : null}
              {loadingMore ? <Spinner /> : null}
            </PanelRow>
          </PanelBody>
        </Panel>
      </>
    );
  }
}

export { ExistingCoupons };
