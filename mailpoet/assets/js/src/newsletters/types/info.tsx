import { Tooltip as WPTooltip } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { ReactNode, ComponentType } from 'react';
import { TooltipProps } from '@wordpress/components/src/tooltip/types';

// Types provided with original Tooltip component define text property as string
// but it supports ReactNode as well
const Tooltip = WPTooltip as ComponentType<
  Omit<TooltipProps, 'text'> & {
    text: ReactNode;
  }
>;

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
