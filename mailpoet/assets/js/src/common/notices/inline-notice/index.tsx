import classnames from 'classnames';
import { info } from '@wordpress/icons';

type InlineNoticeProps = {
  status?: 'info' | 'alert';
  topMessage?: JSX.Element;
  actions?: JSX.Element;
  children: React.ReactNode;
};

export function InlineNotice({
  status = 'info',
  topMessage,
  actions,
  children,
}: InlineNoticeProps): JSX.Element {
  return (
    <div className="mailpoet-inline-notice">
      {topMessage && (
        <div className="mailpoet-inline-notice__top">{topMessage}</div>
      )}
      <div
        className={classnames('mailpoet-inline-notice__card', `is-${status}`)}
      >
        <div className="mailpoet-inline-notice__card-icon">{info}</div>
        <div className="mailpoet-inline-notice__card-content">
          <div className="mailpoet-inline-notice__card-content-body">
            {children}
          </div>
          <div className="mailpoet-inline-notice__card-content-actions">
            {actions}
          </div>
        </div>
      </div>
    </div>
  );
}
