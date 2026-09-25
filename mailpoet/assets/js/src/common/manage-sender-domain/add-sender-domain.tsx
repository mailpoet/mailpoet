import { Button } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { getIsGarden } from 'common/functions';
import { ErrorIcon } from './icons';

const isGarden = getIsGarden();

type Props = {
  senderDomain: string;
  loadingButton: boolean;
  addDomainButtonClicked: () => void;
  error?: string;
};

/**
 * Shown when the sender domain is not on the account yet. Adding it is what generates the DNS
 * records, so it has to happen before they can be displayed - but it changes what the account
 * holds, so it waits for the merchant to ask for it rather than happening on open.
 */
function AddSenderDomain({
  senderDomain,
  loadingButton,
  addDomainButtonClicked,
  error,
}: Props) {
  return (
    <div className="mailpoet_manage_sender_domain_wrapper">
      <div className="mailpoet_add_sender_domain_intro">
        {createInterpolateElement(
          sprintf(
            // translators: %s is a domain name, e.g. example.com.
            __(
              '<strong>%s</strong> has not been added to your account yet.',
              'mailpoet',
            ),
            senderDomain,
          ),
          { strong: <strong /> },
        )}
      </div>

      <div>
        {isGarden
          ? __(
              'Adding it generates the DNS records you need to authenticate it. You can then copy those records into your domain’s DNS settings.',
              'mailpoet',
            )
          : __(
              'Adding it to MailPoet generates the DNS records you need to authenticate it. You can then copy those records into your domain’s DNS settings.',
              'mailpoet',
            )}
      </div>

      {error && (
        <div className="mailpoet_manage_sender_domain_error">
          <ErrorIcon />
          <div>
            <strong>
              {__('Error adding your sender domain.', 'mailpoet')}
            </strong>{' '}
            {error}
          </div>
        </div>
      )}

      <Button
        variant="primary"
        isBusy={loadingButton}
        onClick={addDomainButtonClicked}
      >
        {__('Add domain', 'mailpoet')}
      </Button>
    </div>
  );
}

export { AddSenderDomain };
