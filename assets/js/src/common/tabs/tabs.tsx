import classnames from 'classnames';
import React, { useEffect, useState } from 'react';

import Tab from './tab';

const validateChildren = (children: React.ReactNode): React.ReactElement[] => {
  const keys = {};
  const validChildren: React.ReactElement[] = [];
  React.Children.map(children, (child: React.ReactElement) => {
    if (!child) {
      return;
    }

    if (child.type !== Tab) {
      throw new Error('Child components of <Tabs> must be instances of <Tab>');
    }

    if (child.key === null) {
      throw new Error('Component <Tab> doesn\'t have mandatory "key" attribute');
    }

    if (keys[child.key]) {
      throw new Error(`Duplicate key ${child.key} in <Tabs> children`);
    }
    keys[child.key] = true;
    validChildren.push(child);
  });
  return validChildren;
};

const getActiveChild = (activeTab: string, children: React.ReactElement[]): React.ReactElement => {
  const activeChild = children.find(
    (child) => React.isValidElement(child) && child.key === activeTab
  );
  if (activeChild) {
    return (activeChild);
  }
  throw new Error(`Child <Tab> with key ${activeTab} not found in <Tabs> children`);
};

type Props = {
  activeKey: string;
  onSwitch?: (tabKey: string) => void;
  automationId?: string;
  children: React.ReactNode;
};

const Tabs = ({
  activeKey,
  onSwitch = () => {},
  automationId = null,
  children,
}: Props) => {
  const [activeTab, setActiveTab] = useState(activeKey);
  const [isOpen, setIsOpen] = useState(false);

  // when activeKey changed by a prop let's reflect that in the state
  useEffect(() => {
    switchTab(activeKey);
  }, [activeKey]); // eslint-disable-line react-hooks/exhaustive-deps

  const validChildren = validateChildren(children);
  const activeChild = getActiveChild(activeTab, validChildren);

  const switchTab = (tabKey: string) => {
    setIsOpen(false);
    if (tabKey !== activeTab) {
      setActiveTab(tabKey);
      onSwitch(tabKey);
    }
  };

  const title = (props) => (
    <>
      {props.iconStart}
      {props.title && <span data-title={props.title}>{props.title}</span>}
      {props.iconEnd}
    </>
  );

  return (
    <div className={classnames('mailpoet-tabs', { 'mailpoet-tabs-is-open': isOpen })} data-automation-id={automationId}>
      <button type="button" className="mailpoet-tabs-title" onClick={() => setIsOpen(!isOpen)}>
        {title(activeChild.props)}
      </button>

      <div className="mailpoet-tabs-wrapper">
        {
          validChildren.map((child: React.ReactElement) => (
            <button
              key={child.key}
              className={classnames('mailpoet-tab', { 'mailpoet-tab-active': child === activeChild })}
              type="button"
              role="tab"
              onClick={() => switchTab(child.key.toString())}
              data-automation-id={child.props.automationId}
            >
              {title(child.props)}
            </button>
          ))
        }
      </div>

      <div className="mailpoet-tab-content">{activeChild}</div>
    </div>
  );
};

export default Tabs;
export type { Props };
