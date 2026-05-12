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
      'Sent when a user changes the email address on their profile and needs to confirm it.',
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

const STATUS_META: Record<
  Status,
  { label: string; description: string; className: string }
> = {
  default: {
    label: __('Default', 'mailpoet'),
    description: __('WordPress is sending its built-in email.', 'mailpoet'),
    className: 'mailpoet-wp-emails__status--default',
  },
  customized: {
    label: __('Active', 'mailpoet'),
    description: __('MailPoet is sending this customized email.', 'mailpoet'),
    className: 'mailpoet-wp-emails__status--customized',
  },
  draft: {
    label: __('Draft', 'mailpoet'),
    description: __(
      'Your template is saved, but not being sent yet.',
      'mailpoet',
    ),
    className: 'mailpoet-wp-emails__status--draft',
  },
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

function getResponseError<T>(response: ApiResponse<T>): string | null {
  return response.errors?.[0]?.message ?? null;
}

function getRequestError(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'errors' in error) {
    const errors = (error as { errors?: Array<{ message?: string }> }).errors;
    const message = errors?.find((item) => item.message)?.message;
    if (message) {
      return message;
    }
  }
  return fallback;
}

function formatUpdatedAt(updatedAt: string | null): string | null {
  if (!updatedAt) {
    return null;
  }

  const date = new Date(updatedAt);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return MailPoet.Date.full(updatedAt);
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
      const responseError = getResponseError(response);
      if (responseError) {
        setError(responseError);
        setEmails([]);
        return;
      }
      setEmails(response.data ?? []);
    } catch (e: unknown) {
      setError(
        getRequestError(
          e,
          __('Could not load WordPress email customizations.', 'mailpoet'),
        ),
      );
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
      const responseError = getResponseError(response);
      if (responseError) {
        setError(responseError);
        return;
      }
      const url = response.data?.edit_url;
      if (url) {
        window.location.href = url;
      }
    } catch (e: unknown) {
      setError(
        getRequestError(
          e,
          __('Could not open the email editor for this email.', 'mailpoet'),
        ),
      );
    } finally {
      setBusyKind(null);
    }
  };

  const onToggleActive = async (kind: string, active: boolean) => {
    setBusyKind(kind);
    try {
      const response = await callApi('setActive', { kind, active });
      const responseError = getResponseError(response);
      if (responseError) {
        setError(responseError);
        return;
      }
      await refresh();
    } catch (e: unknown) {
      setError(
        getRequestError(
          e,
          __(
            'Could not update this WordPress email customization.',
            'mailpoet',
          ),
        ),
      );
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
      const response = await callApi('restoreDefault', { kind });
      const responseError = getResponseError(response);
      if (responseError) {
        setError(responseError);
        return;
      }
      await refresh();
    } catch (e: unknown) {
      setError(
        getRequestError(
          e,
          __(
            'Could not restore the WordPress default for this email.',
            'mailpoet',
          ),
        ),
      );
    } finally {
      setBusyKind(null);
    }
  };

  if (loading && emails.length === 0) {
    return (
      <p className="mailpoet-wp-emails__loading">
        {__('Loading WordPress emails…', 'mailpoet')}
      </p>
    );
  }

  return (
    <div className="mailpoet-wp-emails">
      <p className="mailpoet-wp-emails__intro">
        {__(
          'Customize the emails WordPress sends to your users (password reset, account creation, etc.) using the block-based email editor.',
          'mailpoet',
        )}
      </p>
      {error ? (
        <div className="notice notice-error">
          <p>{error}</p>
        </div>
      ) : null}
      <ul className="mailpoet-wp-emails__list">
        {emails.map((email) => {
          const labels = KIND_LABELS[email.kind] ?? {
            title: email.kind,
            description: '',
          };
          const status = STATUS_META[email.status];
          const isCustomized = email.status === 'customized';
          const isBusy = busyKind === email.kind;
          const updatedAt = formatUpdatedAt(email.updated_at);
          return (
            <li
              key={email.kind}
              className={`mailpoet-wp-emails__item mailpoet-wp-emails__item--${email.status}`}
              aria-busy={isBusy}
            >
              <div className="mailpoet-wp-emails__heading">
                <h3>{labels.title}</h3>
                <span
                  className={`mailpoet-wp-emails__status ${status.className}`}
                >
                  {status.label}
                </span>
              </div>
              <p className="mailpoet-wp-emails__description">
                {labels.description}
              </p>
              <p className="mailpoet-wp-emails__status-description">
                {status.description}
              </p>
              {email.subject ? (
                <div className="mailpoet-wp-emails__subject">
                  <span>{__('Subject', 'mailpoet')}</span>
                  {email.subject}
                </div>
              ) : null}
              {updatedAt ? (
                <p className="mailpoet-wp-emails__meta">
                  {__('Last edited', 'mailpoet')}: {updatedAt}
                </p>
              ) : null}
              <div className="mailpoet-wp-emails__actions">
                <Button
                  type="button"
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
                        ? __('Turn off', 'mailpoet')
                        : __('Turn on', 'mailpoet')}
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
