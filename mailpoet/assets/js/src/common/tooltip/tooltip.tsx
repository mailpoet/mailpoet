import classNames from 'classnames';
import ReactTooltip, { TooltipProps } from 'react-tooltip';

function Tooltip({
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
      className={classNames('mailpoet-tooltip', className)}
      border={border === undefined ? true : border}
      {...props}
    >
      {children}
    </ReactTooltip>
  );
}

export default Tooltip;
