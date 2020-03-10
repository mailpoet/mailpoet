import classnames from 'classnames';
import React, { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';

type Props = {
  name: string
  current: string
  children: ReactNode
  automationId: string
}

export default (props: Props) => (
  <Link
    to={`/${props.name}`}
    onClick={() => trackTabClicked(props.name)}
    data-automation-id={props.automationId}
    className={classnames('nav-tab', { 'nav-tab-active': props.name === props.current })}
  >
    {props.children}
  </Link>
);

const trackTabClicked = (tab: string) => {
  MailPoet.trackEvent('User has clicked a tab in Settings', {
    'MailPoet Free version': (window as any).mailpoet_version,
    'Tab ID': tab,
  });
};
