import React from 'react';
import classNames from 'classnames';
import ReactTooltip, { TooltipProps } from 'react-tooltip';

const Tooltip = ({
  effect, textColor, backgroundColor, border, borderColor, className, children, ...props
}: TooltipProps) => (
  <ReactTooltip
    effect={effect || 'solid'}
    textColor={textColor || '#071c6d'}
    backgroundColor={backgroundColor || '#fafbfe'}
    borderColor={borderColor || '#e5e9f8'}
    className={classNames('mailpoet-tooltip', className)}
    border={border === undefined ? true : border}
    {...props}
  >
    {children}
  </ReactTooltip>
);

export default Tooltip;
