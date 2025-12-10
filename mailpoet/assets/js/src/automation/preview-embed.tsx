/**
 * Automation Preview Embed Entry Point
 *
 * This is a minimal entry point for rendering automation template previews
 * in an iframe.
 */
import { createRoot } from 'react-dom/client';
import { __ } from '@wordpress/i18n';
import { registerTranslations } from 'common';
import { initializeApi } from './api';
import { TemplatePreview } from './templates/components/template-preview';

declare global {
  interface Window {
    mailpoet_template_slug?: string;
  }
}

function PreviewEmbed(): JSX.Element {
  const templateSlug = window.mailpoet_template_slug;

  if (!templateSlug) {
    return (
      <div className="preview-error">
        {__('No template specified', 'mailpoet')}
      </div>
    );
  }

  return (
    <TemplatePreview
      template={{
        slug: templateSlug,
        name: '',
        description: '',
        category: 'custom',
        type: 'default',
        required_capabilities: {},
      }}
    />
  );
}

window.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('mailpoet_automation_preview');
  if (!container) {
    return;
  }

  registerTranslations();
  initializeApi();

  const root = createRoot(container);
  root.render(<PreviewEmbed />);
});
