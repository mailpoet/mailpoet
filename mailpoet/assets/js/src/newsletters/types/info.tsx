import { Tooltip } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { ReactNode } from 'react';

type Props = {
  children: ReactNode;
};

export function Info({ children }: Props): JSX.Element {
  return (
    <Tooltip
      delay={0}
      text={
        <div className="mailpoet-newsletter-type-info-tooltip">{children}</div>
      }
    >
      <div className="mailpoet-newsletter-type-info">
        <Icon icon={info} size={20} />
      </div>
    </Tooltip>
  );
}
