import classnames from 'classnames';
import {
  Children,
  isValidElement,
  type ReactElement,
  ReactNode,
  useEffect,
  useState,
} from 'react';
import { noop } from 'lodash';

import { Tab, type TabProps } from './tab';

type TabElement = ReactElement<TabProps, typeof Tab>;

const isTabElement = (child: ReactNode): child is TabElement =>
  isValidElement<TabProps>(child) && child.type === Tab;

const validateChildren = (children: ReactNode): TabElement[] => {
  const keys: Record<string, boolean> = {};
  const validChildren: TabElement[] = [];
  Children.forEach(children, (child) => {
    if (!child) {
      return;
    }

    if (!isTabElement(child)) {
      throw new Error('Child components of <Tabs> must be instances of <Tab>');
    }

    if (child.key === null) {
      throw new Error(
        'Component <Tab> doesn\'t have mandatory "key" attribute',
      );
    }

    if (keys[child.key]) {
      throw new Error(`Duplicate key ${child.key} in <Tabs> children`);
    }
    keys[child.key] = true;
    validChildren.push(child);
  });
  return validChildren;
};

const getActiveChild = (
  activeTab: string,
  children: TabElement[],
): TabElement => {
  const activeChild = children.find(
    (child) => isValidElement(child) && child.key === activeTab,
  );
  if (activeChild) {
    return activeChild;
  }
  throw new Error(
    `Child <Tab> with key ${activeTab} not found in <Tabs> children`,
  );
};

type Props = {
  activeKey: string;
  onSwitch?: (tabKey: string) => void;
  automationId?: string;
  children: ReactNode;
};

export function Tabs({
  activeKey,
  onSwitch = noop,
  automationId = null,
  children,
}: Props) {
  const [activeTab, setActiveTab] = useState(activeKey);

  const switchTab = (tabKey: string) => {
    if (tabKey !== activeTab) {
      setActiveTab(tabKey);
      onSwitch(tabKey);
    }
  };

  // when activeKey changed by a prop let's reflect that in the state
  useEffect(() => {
    switchTab(activeKey);
  }, [activeKey]); // eslint-disable-line react-hooks/exhaustive-deps

  const validChildren = validateChildren(children);
  const activeChild = getActiveChild(activeTab, validChildren);

  const renderTitle = ({
    iconStart,
    title: tabTitle,
    iconEnd,
  }: TabProps): JSX.Element => (
    <>
      {iconStart}
      {tabTitle && <span data-title={tabTitle}>{tabTitle}</span>}
      {iconEnd}
    </>
  );

  return (
    <div
      className="mailpoet-categories mailpoet-tabs"
      data-automation-id={automationId}
    >
      <div className="components-tab-panel__tabs">
        {validChildren.map((child) => (
          <button
            key={child.key}
            className={classnames(
              'components-button',
              'components-tab-panel__tabs-item',
              { 'is-active': child === activeChild },
              String(child.props?.className || ''),
            )}
            type="button"
            role="tab"
            onClick={() => switchTab(child.key.toString())}
            data-automation-id={child.props.automationId}
          >
            {renderTitle(child.props)}
          </button>
        ))}
      </div>

      <div className="mailpoet-tab-content">{activeChild}</div>
    </div>
  );
}

export type { Props };
export { isTabElement };
