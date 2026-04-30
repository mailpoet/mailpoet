import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';

type Status = 'default' | 'customized' | 'draft';

type WpEmail = {
  kind: string;
  status: Status;
  newsletter_id: number | null;
  subject: string | null;
  edit_url: string | null;
  updated_at: string | null;
};

type ApiResponse<T> = { data?: T; errors?: Array<{ message: string }> };

const KIND_LABELS: Record<string, { title: string; description: string }> = {
  password_reset: {
    title: __('Password reset', 'mailpoet'),
    description: __(
      'Sent when a user requests a new password from the login page.',
      'mailpoet',
    ),
  },
  new_user: {
    title: __('New user welcome', 'mailpoet'),
    description: __(
      'Sent the first time a new account is created on your site.',
      'mailpoet',
    ),
  },
  email_change: {
    title: __('Email change confirmation', 'mailpoet'),
    description: __(
      "Sent when a user changes the email address on their profile and needs to confirm it.",
      'mailpoet',
    ),
  },
  password_change: {
    title: __('Password change confirmation', 'mailpoet'),
    description: __(
      'Sent after a user successfully changes their password.',
      'mailpoet',
    ),
  },
};

const STATUS_LABELS: Record<Status, string> = {
  default: __('WordPress default', 'mailpoet'),
  customized: __('Customized', 'mailpoet'),
  draft: __('Draft (off)', 'mailpoet'),
};

async function callApi<T>(
  action: string,
  data: Record<string, unknown> = {},
): Promise<ApiResponse<T>> {
  const response = await MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'wpTransactionalEmails',
    action,
    data,
  });
  return response as ApiResponse<T>;
}

export function WpEmails(): JSX.Element {
  const [emails, setEmails] = useState<WpEmail[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyKind, setBusyKind] = useState<string | null>(null);

  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await callApi<WpEmail[]>('listAll');
      if (response.errors && response.errors[0]) {
        setError(response.errors[0].message);
        setEmails([]);
        return;
      }
      setEmails(response.data ?? []);
    } catch (e) {
      setError(__('Could not load WordPress email customizations.', 'mailpoet'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void refresh();
  }, []);

  const onCustomize = async (kind: string) => {
    setBusyKind(kind);
    try {
      const response = await callApi<{ edit_url: string }>('customize', {
        kind,
      });
      if (response.errors && response.errors[0]) {
        setError(response.errors[0].message);
        return;
      }
      const url = response.data?.edit_url;
      if (url) {
        window.location.href = url;
      }
    } finally {
      setBusyKind(null);
    }
  };

  const onToggleActive = async (kind: string, active: boolean) => {
    setBusyKind(kind);
    try {
      await callApi('setActive', { kind, active });
      await refresh();
    } finally {
      setBusyKind(null);
    }
  };

  const onRestoreDefault = async (kind: string) => {
    if (
      // eslint-disable-next-line no-alert -- consistent with reinstall confirmation in this settings area
      !window.confirm(
        __(
          'Restore the WordPress default for this email? Your customizations for this email will be removed.',
          'mailpoet',
        ),
      )
    ) {
      return;
    }
    setBusyKind(kind);
    try {
      await callApi('restoreDefault', { kind });
      await refresh();
    } finally {
      setBusyKind(null);
    }
  };

  if (loading) {
    return <p>{__('Loading…', 'mailpoet')}</p>;
  }

  if (error) {
    return (
      <div className="notice notice-error">
        <p>{error}</p>
      </div>
    );
  }

  return (
    <div className="mailpoet-wp-emails">
      <p>
        {__(
          'Customize the emails WordPress sends to your users (password reset, account creation, etc.) using the block-based email editor.',
          'mailpoet',
        )}
      </p>
      <ul className="mailpoet-wp-emails__list">
        {emails.map((email) => {
          const labels = KIND_LABELS[email.kind] ?? {
            title: email.kind,
            description: '',
          };
          const isCustomized = email.status === 'customized';
          const isBusy = busyKind === email.kind;
          return (
            <li
              key={email.kind}
              className={`mailpoet-wp-emails__item mailpoet-wp-emails__item--${email.status}`}
            >
              <div className="mailpoet-wp-emails__heading">
                <h3>{labels.title}</h3>
                <span className="mailpoet-wp-emails__status">
                  {STATUS_LABELS[email.status]}
                </span>
              </div>
              <p>{labels.description}</p>
              <div className="mailpoet-wp-emails__actions">
                <Button
                  type="button"
                  variant="secondary"
                  dimension="small"
                  onClick={() => onCustomize(email.kind)}
                  withSpinner={isBusy}
                  isDisabled={isBusy}
                >
                  {email.newsletter_id
                    ? __('Edit template', 'mailpoet')
                    : __('Customize', 'mailpoet')}
                </Button>
                {email.newsletter_id ? (
                  <>
                    <Button
                      type="button"
                      variant="secondary"
                      dimension="small"
                      onClick={() => onToggleActive(email.kind, !isCustomized)}
                      isDisabled={isBusy}
                    >
                      {isCustomized
                        ? __('Disable', 'mailpoet')
                        : __('Enable', 'mailpoet')}
                    </Button>
                    <Button
                      type="button"
                      variant="tertiary"
                      dimension="small"
                      onClick={() => onRestoreDefault(email.kind)}
                      isDisabled={isBusy}
                    >
                      {__('Restore default', 'mailpoet')}
                    </Button>
                  </>
                ) : null}
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
