import classnames from 'classnames';
import { Tooltip as ReactTooltip, ITooltip } from 'react-tooltip';

export function Tooltip({ className, children, ...props }: ITooltip) {
  return (
    <ReactTooltip
      className={classnames('mailpoet-tooltip', className)}
      {...props}
    >
      {children}
    </ReactTooltip>
  );
}
