import { Panel, PanelRow, PanelBody, TextControl } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { GetValueCallback, SetValueCallback } from './types';

type Props = {
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
};

type State = {
  usageLimit: string;
  usageLimitPerUser: string;
};

class UsageLimits extends Component<Props, State> {
  private readonly getValueCallback: GetValueCallback;

  private readonly setValueCallback: SetValueCallback;

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
          title={__('Usage limits', 'mailpoet')}
          className="mailpoet-coupon-block-usage-limits"
          initialOpen={false}
        >
          <PanelRow>
            <TextControl
              label={__('Usage limit per coupon', 'mailpoet')}
              value={this.state.usageLimit}
              placeholder={__('Unlimited usage', 'mailpoet')}
              type="number"
              onChange={(usageLimit) => {
                this.setValueCallback('usageLimit', usageLimit);
                this.setState({ usageLimit });
              }}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__('Usage limit per user', 'mailpoet')}
              value={this.state.usageLimitPerUser}
              placeholder={__('Unlimited usage', 'mailpoet')}
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
