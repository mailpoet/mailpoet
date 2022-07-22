import { Tooltip } from 'common/tooltip/tooltip';
import { MailPoet } from 'mailpoet';

const DomainStatus = {
  pending: 'pending',
  valid: 'valid',
  invalid: 'invalid',
};

type Props = {
  status: string;
  message: string;
  index: number;
};
function DomainStatusComponent({ status, message, index }: Props) {
  let content = null;

  if (status === DomainStatus.pending) {
    content = (
      <div>
        <span className="dashicons dashicons-update-alt" />
        {'  '}
        {MailPoet.I18n.t('manageSenderDomainStatusPending')}
      </div>
    );
  }

  if (status === DomainStatus.valid) {
    content = (
      <div>
        <span className="dashicons dashicons-yes-alt mailpoet_success" />
        {'  '}
        {MailPoet.I18n.t('manageSenderDomainStatusVerified')}
      </div>
    );
  }

  if (status === DomainStatus.invalid) {
    content = (
      <div className="relative-holder">
        <span className="dashicons dashicons-no-alt mailpoet_error" />
        {'  '}
        {MailPoet.I18n.t('manageSenderDomainStatusInvalid')}
        {'    '}
        <span
          className="mailpoet-form-tooltip-without-icon"
          data-tip
          data-for={`invalid_dns_${index}`}
        >
          <span className="dashicons dashicons-info" />
        </span>
        <Tooltip id={`invalid_dns_${index}`} place="top">
          <span> {message} </span>
        </Tooltip>
      </div>
    );
  }

  return <div>{content}</div>;
}

export { DomainStatusComponent };
