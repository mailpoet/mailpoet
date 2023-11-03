import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import { createRoot } from 'react-dom/client';
import { ListingHeadingSteps } from 'newsletters/listings/heading-steps';
import { newsletterTypesWithActivation } from 'newsletters/listings/utils';
import { fetchAutomaticEmailShortcodes } from 'newsletters/automatic-emails/fetch-editor-shortcodes.jsx';
import { ErrorBoundary } from 'common';
import { initTutorial } from './tutorial';

const renderHeading = (newsletterType, newsletterOptions) => {
  if (!['wc_transactional', 'confirmation_email'].includes(newsletterType)) {
    const stepsHeadingContainer = document.getElementById(
      'mailpoet_editor_steps_heading',
    );
    const step = ['automation', 'automation_transactional'].includes(
      newsletterType,
    )
      ? 2
      : 3;

    let buttons = null;
    let onLogoClick = () => {
      window.location = `admin.php?page=${MailPoet.mainPageSlug}`;
    };
    if (newsletterOptions.automationId) {
      const automationId = newsletterOptions.automationId;
      const goToUrl = `admin.php?page=mailpoet-automation-editor&id=${automationId}`;
      onLogoClick = () => {
        window.location = goToUrl;
      };
      // These actions are set up from Marionette, we just trigger them here.
      const onClickPreview = () =>
        document.querySelector('.mailpoet_show_preview').click();
      const onClickSave = () =>
        document.querySelector('.mailpoet_save_go_to_automation').click();
      buttons = (
        <>
          <input
            type="button"
            name="preview"
            className="button link-button"
            onClick={onClickPreview}
            value="Preview"
          />{' '}
          <input
            type="button"
            className="button button-primary"
            onClick={onClickSave}
            value="Save and continue"
          />
        </>
      );
    }

    const stepsHeading = (
      <ErrorBoundary>
        <ListingHeadingSteps
          emailType={newsletterType}
          step={step}
          buttons={buttons}
          onLogoClick={onLogoClick}
        />
      </ErrorBoundary>
    );

    const root = createRoot(stepsHeadingContainer);
    root.render(stepsHeading);
  }
};

const initializeEditor = (config) => {
  const editorContainer = document.getElementById('mailpoet_editor');
  const getUrlParam = (param) =>
    (document.location.search.split(`${param}=`)[1] || '').split('&')[0];

  if (!editorContainer || !window.EditorApplication) return;

  MailPoet.Modal.loading(true);

  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'get',
    data: {
      id: getUrlParam('id'),
    },
  })
    .always(() => MailPoet.Modal.loading(false))
    .always(() => initTutorial())
    .done((response) => {
      const newsletter = response.data;

      if (
        newsletter.status === 'sending' &&
        newsletter.queue &&
        newsletter.queue.status === null
      ) {
        // Users can confirm before visiting the page
        let pauseConfirmed = getUrlParam('pauseConfirmed') === 'yes';
        if (!pauseConfirmed) {
          // eslint-disable-next-line no-alert
          pauseConfirmed = window.confirm(
            __(
              'Sending is in progress. Do you want to pause sending and edit the newsletter?',
              'mailpoet',
            ),
          );
        }
        if (!pauseConfirmed) {
          window.location = `admin.php?page=mailpoet-newsletters#/${newsletter.type}`;
          return;
        }
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'sending_queue',
          action: 'pause',
          data: {
            newsletter_id: newsletter.id,
          },
        })
          .done(() =>
            MailPoet.Notice.system(
              __('Email sending has been paused.', 'mailpoet'),
            ),
          )
          .fail((pauseFailResponse) => {
            if (pauseFailResponse.errors.length > 0) {
              MailPoet.Notice.error(
                pauseFailResponse.errors.map((error) => error.message),
                { scroll: true, static: true },
              );
            }
          });
      } else if (
        newsletterTypesWithActivation.includes(newsletter.type) &&
        newsletter.status === 'active'
      ) {
        // Users can confirm before visiting the page
        let deactivationConfirmed =
          getUrlParam('deactivationConfirmed') === 'yes';
        if (!deactivationConfirmed) {
          // eslint-disable-next-line no-alert
          deactivationConfirmed = window.confirm(
            __(
              'To edit this email, it needs to be deactivated. You can activate it again after you make the changes.',
              'mailpoet',
            ),
          );
        }
        if (!deactivationConfirmed) {
          window.location = `admin.php?page=mailpoet-newsletters#/${newsletter.type}`;
          return;
        }
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'setStatus',
          data: {
            id: newsletter.id,
            status: 'draft',
          },
        })
          .done((setStatusResponse) => {
            if (setStatusResponse.data.status === 'draft') {
              MailPoet.Notice.system(
                __('This email was deactivated.', 'mailpoet'),
              );
            }
          })
          .fail((pauseFailResponse) => {
            MailPoet.Notice.error(
              pauseFailResponse.errors.map((error) => error.message),
              { scroll: true, static: true },
            );
          });
      }
      Promise.resolve(fetchAutomaticEmailShortcodes(config, newsletter))
        .then((extendedConfig) => {
          const blockDefaults = {
            ...extendedConfig.blockDefaults,
            container: {},
          };
          window.EditorApplication.start({
            newsletter,
            config: { ...extendedConfig, blockDefaults },
          });
        })
        .catch(() => {
          window.EditorApplication.start({
            newsletter,
            config,
          });
        });

      renderHeading(
        newsletter.type === 'automatic'
          ? newsletter.options?.group
          : newsletter.type,
        newsletter.options,
      );
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true, static: true },
        );
      }
    });
};

Hooks.addAction(
  'mailpoet_newsletters_editor_initialize',
  'mailpoet',
  initializeEditor,
);
