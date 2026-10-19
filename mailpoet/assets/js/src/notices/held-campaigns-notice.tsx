import { useLayoutEffect, useRef, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet';
import { withBoundary } from 'common';
import { Button } from 'common/button/button';
import { confirmAlert } from 'common/confirm-alert';
import { Notice } from 'notices/notice';
import { APIErrorsNotice } from 'notices/api-errors-notice';
import {
  BusyRows,
  clearBusy,
  confirmCopy,
  HeldRowAction,
  markBusy,
  nextFocusIndex,
  rowActions,
  scheduleText,
  subjectText,
  visibleRows,
  VISIBLE_ROWS_LIMIT,
} from './held-campaigns';

type Props = {
  campaigns: HeldCampaign[];
  onRemove: (id: number) => void;
  onRefresh: () => void;
};

type FocusRequest =
  | { rowIndex: number }
  | { rowId: number; action: HeldRowAction };

function getLabel(action: HeldRowAction): string {
  if (action === 'resume') return __('Send now', 'mailpoet');
  if (action === 'reschedule') return __('Reschedule', 'mailpoet');
  return __('Cancel', 'mailpoet');
}

function HeldCampaignsNotice({ campaigns, onRemove, onRefresh }: Props) {
  const [showAll, setShowAll] = useState(false);
  const [busy, setBusy] = useState<BusyRows>({});
  const [errors, setErrors] = useState<{ message: string }[]>([]);
  const [focusRequest, setFocusRequest] = useState<FocusRequest | null>(null);
  const headingRef = useRef<HTMLHeadingElement>(null);
  const listRef = useRef<HTMLUListElement>(null);

  useLayoutEffect(() => {
    if (!focusRequest) return;
    if ('rowId' in focusRequest) {
      const row = listRef.current?.querySelector(
        `[data-held-id="${focusRequest.rowId}"]`,
      );
      const index = rowActions(
        campaigns.find((item) => item.id === focusRequest.rowId),
      ).indexOf(focusRequest.action);
      row?.querySelectorAll<HTMLElement>('button')[index]?.focus();
    } else {
      const item = listRef.current?.children[focusRequest.rowIndex];
      const button = item?.querySelector<HTMLElement>('button');
      const target =
        button ??
        headingRef.current ??
        document.getElementById('mailpoet_held_campaigns');
      target?.setAttribute('tabindex', '-1');
      target?.focus();
    }
    setFocusRequest(null);
  }, [focusRequest, campaigns]);

  if (campaigns.length === 0) return <APIErrorsNotice errors={errors} />;

  const visible = visibleRows(campaigns, showAll);
  const heading = sprintf(
    __('Emails held for your confirmation (%d)', 'mailpoet'),
    campaigns.length,
  );

  const finish = (row: HeldCampaign, announcement: string) => {
    const index = visible.findIndex((item) => item.id === row.id);
    const rowIndex = nextFocusIndex(visible, index, campaigns.length);
    setBusy((current) => clearBusy(current, row.id));
    onRemove(row.id);
    setFocusRequest({ rowIndex });
    speak(announcement, 'polite');
    jQuery(document).trigger('heartbeat-tick.mailpoet-listing');
  };

  const fail = (
    row: HeldCampaign,
    action: HeldRowAction,
    response?: { errors?: { message: string }[] },
  ) => {
    setBusy((current) => clearBusy(current, row.id));
    setErrors(response?.errors ?? []);
    setFocusRequest({ rowId: row.id, action });
    onRefresh();
  };

  const post = (
    row: HeldCampaign,
    action: HeldRowAction,
    endpoint: 'resume' | 'cancelHeld',
    data: Record<string, unknown>,
    onSuccess: () => void,
  ) => {
    setBusy((current) => markBusy(current, row.id, action));
    setErrors([]);
    return MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: endpoint,
      data,
    })
      .done(onSuccess)
      .fail((response) => fail(row, action, response));
  };

  const subjectOf = (row: HeldCampaign) => subjectText(row.subject);

  const handlers: Record<HeldRowAction, (row: HeldCampaign) => void> = {
    resume: (row) => {
      void post(row, 'resume', 'resume', { newsletter_id: row.id }, () =>
        finish(
          row,
          sprintf(__('Send confirmed: %s', 'mailpoet'), subjectOf(row)),
        ),
      );
    },
    cancel: (row) => {
      confirmAlert({
        message: confirmCopy(row.type, 'cancel'),
        confirmLabel: __('Cancel email', 'mailpoet'),
        cancelLabel: __('Keep it held', 'mailpoet'),
        onConfirm: () =>
          post(row, 'cancel', 'cancelHeld', { newsletter_id: row.id }, () =>
            finish(
              row,
              sprintf(__('Cancelled: %s', 'mailpoet'), subjectOf(row)),
            ),
          ),
      });
    },
    reschedule: (row) => {
      confirmAlert({
        message: confirmCopy(row.type, 'reschedule'),
        confirmLabel: __('Reschedule', 'mailpoet'),
        cancelLabel: __('Keep it held', 'mailpoet'),
        onConfirm: () =>
          post(
            row,
            'reschedule',
            'cancelHeld',
            { newsletter_id: row.id, reschedule: true },
            () => {
              setBusy((current) => clearBusy(current, row.id));
              onRemove(row.id);
              if (row.can_edit_link) window.location.href = row.can_edit_link;
            },
          ),
      });
    },
  };

  return (
    <>
      <APIErrorsNotice errors={errors} />
      <Notice type="warning" renderInPlace timeout={false} closable={false}>
        <div role="region" aria-label={heading}>
          <h3 tabIndex={-1} ref={headingRef}>
            {heading}
          </h3>
          <p>
            {__(
              'These emails are late, so MailPoet didn’t send them. Choose what to do with each one.',
              'mailpoet',
            )}
          </p>
          <ul className="mailpoet-held-campaigns" ref={listRef}>
            {visible.map((row) => {
              const busyAction = busy[row.id];
              const rowBusy = busyAction !== undefined;
              return (
                <li
                  key={row.id}
                  className="mailpoet-held-campaigns-row"
                  data-held-id={row.id}
                >
                  <div className="mailpoet-held-campaigns-info">
                    {row.can_edit_link ? (
                      <a
                        className="mailpoet-held-campaigns-subject"
                        href={row.can_edit_link}
                      >
                        {subjectOf(row)}
                      </a>
                    ) : (
                      <span className="mailpoet-held-campaigns-subject">
                        {subjectOf(row)}
                      </span>
                    )}
                    <div>
                      {scheduleText(row, (date) => MailPoet.Date.full(date))}
                    </div>
                  </div>
                  <div className="mailpoet-held-campaigns-actions">
                    {rowActions(row).map((action) => (
                      <Button
                        key={action}
                        dimension="small"
                        variant={action === 'resume' ? undefined : 'secondary'}
                        withSpinner={busyAction === action}
                        isDisabled={rowBusy}
                        onClick={() => handlers[action](row)}
                      >
                        {getLabel(action)}
                        <span className="screen-reader-text">
                          {`: ${subjectOf(row)}`}
                        </span>
                      </Button>
                    ))}
                  </div>
                </li>
              );
            })}
          </ul>
          {!showAll && campaigns.length > VISIBLE_ROWS_LIMIT && (
            <p>
              <Button
                dimension="small"
                variant="tertiary"
                onClick={() => setShowAll(true)}
              >
                {sprintf(
                  __('Show all %d held emails', 'mailpoet'),
                  campaigns.length,
                )}
              </Button>
            </p>
          )}
        </div>
      </Notice>
    </>
  );
}

HeldCampaignsNotice.displayName = 'HeldCampaignsNotice';
const HeldCampaignsNoticeWithBoundary = withBoundary(HeldCampaignsNotice);
export { HeldCampaignsNoticeWithBoundary as HeldCampaignsNotice };
