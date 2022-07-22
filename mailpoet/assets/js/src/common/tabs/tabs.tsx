import classnames from 'classnames';
import {
  Children,
  isValidElement,
  ReactElement,
  ReactNode,
  useEffect,
  useState,
} from 'react';
import { noop } from 'lodash';

import { Tab } from './tab';

const validateChildren = (children: ReactNode): ReactElement[] => {
  const keys = {};
  const validChildren: ReactElement[] = [];
  Children.map(children, (child: ReactElement) => {
    if (!child) {
      return;
    }

    if (child.type !== Tab) {
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
  children: ReactElement[],
): ReactElement => {
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
  const [isOpen, setIsOpen] = useState(false);

  const switchTab = (tabKey: string) => {
    setIsOpen(false);
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

  const title = (props) => (
    <>
      {props.iconStart}
      {props.title && <span data-title={props.title}>{props.title}</span>}
      {props.iconEnd}
    </>
  );

  return (
    <div
      className={classnames('mailpoet-tabs', {
        'mailpoet-tabs-is-open': isOpen,
      })}
      data-automation-id={automationId}
    >
      <button
        type="button"
        className="mailpoet-tabs-title"
        onClick={() => setIsOpen(!isOpen)}
      >
        {title(activeChild.props)}
      </button>

      <div className="mailpoet-tabs-wrapper">
        {validChildren.map((child: ReactElement) => (
          <button
            key={child.key}
            className={classnames(
              'mailpoet-tab',
              {
                'mailpoet-tab-active': child === activeChild,
              },
              String(child.props?.className || ''),
            )}
            type="button"
            role="tab"
            onClick={() => switchTab(child.key.toString())}
            data-automation-id={child.props.automationId}
          >
            {title(child.props)}
          </button>
        ))}
      </div>

      <div className="mailpoet-tab-content">{activeChild}</div>
    </div>
  );
}

export type { Props };
