import { action } from '_storybook/action';
import { CSSProperties } from 'react';
import Heading from '../../typography/heading/heading';
import RoutedTabs from '../routed_tabs';
import Tab from '../tab';
import Tabs from '../tabs';
import icon from './assets/icon';

const wrapperStyles: CSSProperties = {
  background: '#f1f1f1',
  width: '100%',
  height: '100%',
  padding: '20px',
  boxSizing: 'border-box',
};

export default {
  title: 'Tabs',
  component: Tabs,
};

export function WithIcons() {
  return (
    <div style={wrapperStyles}>
      <Heading level={3}>Tabs</Heading>
      <Tabs activeKey="first" onSwitch={action('onSwitchTabs')}>
        <Tab key="first" title="Before" iconStart={icon}>
          First tab content
        </Tab>
        <Tab key="second" title="After" iconEnd={icon}>
          Second tab content
        </Tab>
        <Tab key="third" title="Both" iconStart={icon} iconEnd={icon}>
          Third tab content
        </Tab>
      </Tabs>

      <div className="mailpoet-gap" />

      <Heading level={3}>Nested tabs</Heading>
      <Tabs activeKey="first" onSwitch={action('onSwitchNestedTabsRoot')}>
        <Tab key="first" title="Before" iconStart={icon}>
          <Tabs activeKey="first" onSwitch={action('onSwitchNestedTabsChild')}>
            <Tab key="first" title="Before" iconStart={icon}>
              First tab content
            </Tab>
            <Tab key="second" title="After" iconEnd={icon}>
              Second tab content
            </Tab>
            <Tab key="third" title="Both" iconStart={icon} iconEnd={icon}>
              Third tab content
            </Tab>
          </Tabs>
        </Tab>
        <Tab key="second" title="After" iconEnd={icon}>
          Second tab content
        </Tab>
        <Tab key="third" title="Both" iconStart={icon} iconEnd={icon}>
          Third tab content
        </Tab>
      </Tabs>

      <div className="mailpoet-gap" />

      <Heading level={3}>
        Routed tabs (work Back/Forward browser buttons)
      </Heading>
      <RoutedTabs activeKey="first" onSwitch={action('onSwitchRoutedTabs')}>
        <Tab key="first" title="Before" iconStart={icon}>
          First tab content
        </Tab>
        <Tab key="second" title="After" iconEnd={icon}>
          Second tab content
        </Tab>
        <Tab key="third" title="Both" iconStart={icon} iconEnd={icon}>
          Third tab content
        </Tab>
      </RoutedTabs>
    </div>
  );
}
