import classnames from 'classnames';
import ReactTooltip, { TooltipProps } from 'react-tooltip';

export function Tooltip({
  effect,
  textColor,
  backgroundColor,
  border,
  borderColor,
  className,
  children,
  ...props
}: TooltipProps) {
  return (
    <ReactTooltip
      effect={effect || 'solid'}
      textColor={textColor || '#1d2327'}
      backgroundColor={backgroundColor || '#fafbfe'}
      borderColor={borderColor || '#e5e9f8'}
      className={classnames('mailpoet-tooltip', className)}
      border={border === undefined ? true : border}
      {...props}
    >
      {children}
    </ReactTooltip>
  );
}
