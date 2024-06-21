import classnames from 'classnames';
import { Tooltip as ReactTooltip, ITooltip } from 'react-tooltip';

export function Tooltip({ border, className, children, ...props }: ITooltip) {
  return (
    <ReactTooltip
      className={classnames('mailpoet-tooltip', className)}
      border={border === undefined ? true : border}
      {...props}
    >
      {children}
    </ReactTooltip>
  );
}
