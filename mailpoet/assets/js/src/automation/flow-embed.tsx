/**
 * Automation Flow Embed Entry Point
 *
 * This is a minimal entry point for rendering the automation flow diagram
 * with statistics in an iframe.
 */
import { createRoot } from 'react-dom/client';
import { dispatch, select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { registerTranslations } from 'common';
import { initializeApi } from './api';
import { initializeIntegrations } from './editor/integrations';
import { createStore as editorStoreCreate } from './editor/store';
import {
  createStore as analyticsStoreCreate,
  Section,
  storeName as analyticsStoreName,
} from './integrations/mailpoet/analytics/store';
import { initializeApi as initializeAnalyticsApi } from './integrations/mailpoet/analytics/api';
import { AutomationFlow } from './integrations/mailpoet/analytics/components/tabs/automation-flow';

declare global {
  interface Window {
    mailpoet_automation_id?: number;
    mailpoet_automation_api: {
      root: string;
      nonce: string;
    };
  }
}

function FlowEmbed(): JSX.Element {
  const automationId = window.mailpoet_automation_id;

  if (!automationId) {
    return (
      <div className="flow-embed-error">
        {__('No automation specified', 'mailpoet')}
      </div>
    );
  }

  return (
    <div className="mailpoet-automation-flow-embed">
      <AutomationFlow />
    </div>
  );
}

function boot() {
  initializeAnalyticsApi();
  select(analyticsStoreName)
    .getSections()
    .forEach((section: Section) => {
      void dispatch(analyticsStoreName).updateSection(section);
    });
}

window.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('mailpoet_automation_flow_embed');
  if (!container) {
    return;
  }

  registerTranslations();
  initializeApi();
  editorStoreCreate();
  analyticsStoreCreate();
  initializeIntegrations();
  boot();

  const root = createRoot(container);
  root.render(<FlowEmbed />);

  // Height communication for iframe embedding
  if (window.parent !== window) {
    let lastHeight = 0;

    const sendHeightToParent = () => {
      const height = document.body.scrollHeight;
      if (height > 0 && height !== lastHeight) {
        lastHeight = height;
        window.parent.postMessage(
          { type: 'mailpoet-flow-embed-height', height },
          '*',
        );
      }
    };

    // Use ResizeObserver to detect height changes
    const resizeObserver = new ResizeObserver(sendHeightToParent);
    resizeObserver.observe(document.body);

    // Also send on window load
    window.addEventListener('load', sendHeightToParent);
  }
});
