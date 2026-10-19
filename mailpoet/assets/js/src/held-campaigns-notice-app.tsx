import { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { HeldCampaignsNotice } from 'notices/held-campaigns-notice';
import { removeRow } from 'notices/held-campaigns';

const REFRESH_ACTION = 'mailpoet.held_campaigns.refresh';

function HeldCampaignsApp() {
  const [campaigns, setCampaigns] = useState<HeldCampaign[]>(
    window.mailpoet_held_campaigns ?? [],
  );

  const refetch = useCallback(() => {
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sendingQueue',
      action: 'getHeld',
    })
      .done((response) => setCampaigns(response.data as HeldCampaign[]))
      .fail(() => {});
  }, []);

  useEffect(() => {
    Hooks.addAction(REFRESH_ACTION, 'mailpoet', refetch);
    return () => {
      Hooks.removeAction(REFRESH_ACTION, 'mailpoet');
    };
  }, [refetch]);

  return (
    <HeldCampaignsNotice
      campaigns={campaigns}
      onRemove={(id) => setCampaigns((rows) => removeRow(rows, id))}
      onRefresh={refetch}
    />
  );
}

const container = document.getElementById('mailpoet_held_campaigns');
if (container) {
  createRoot(container).render(<HeldCampaignsApp />);
}
