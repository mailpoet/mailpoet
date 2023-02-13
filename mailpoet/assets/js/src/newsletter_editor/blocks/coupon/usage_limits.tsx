import { Panel, PanelRow, PanelBody, TextControl } from '@wordpress/components';
import Backbone from 'backbone';
import { Component } from '@wordpress/element';
import { MailPoet } from 'mailpoet';

type Props = {
  getValueCallback: (name: string) => string | boolean | Backbone.Collection;
  setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;
};

type State = {
  usageLimit: string;
  usageLimitPerUser: string;
};

class UsageLimits extends Component<Props, State> {
  private readonly getValueCallback: (
    name: string,
  ) => string | boolean | Backbone.Collection;

  private readonly setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;

  constructor(props: Props) {
    super(props);
    this.getValueCallback = props.getValueCallback;
    this.setValueCallback = props.setValueCallback;

    this.state = {
      usageLimit: this.getValueCallback('usageLimit') as string,
      usageLimitPerUser: this.getValueCallback('usageLimitPerUser') as string,
    };
  }

  render() {
    return (
      <Panel>
        <PanelBody
          title={MailPoet.I18n.t('usageLimits')}
          className="mailpoet-coupon-block-usage-limits"
          initialOpen={false}
        >
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_usage_limits_per_coupon"
              label={MailPoet.I18n.t('usageLimitPerCoupon')}
              value={this.state.usageLimit}
              placeholder={MailPoet.I18n.t('unlimitedUsage')}
              type="number"
              onChange={(usageLimit) => {
                this.setValueCallback('usageLimit', usageLimit);
                this.setState({ usageLimit });
              }}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              className="mailpoet_field_coupon_usage_limits_per_user"
              label={MailPoet.I18n.t('usageLimitPerUser')}
              value={this.state.usageLimitPerUser}
              placeholder={MailPoet.I18n.t('unlimitedUsage')}
              type="number"
              onChange={(usageLimitPerUser) => {
                this.setValueCallback('usageLimitPerUser', usageLimitPerUser);
                this.setState({ usageLimitPerUser });
              }}
            />
          </PanelRow>
        </PanelBody>
      </Panel>
    );
  }
}

export { UsageLimits };
