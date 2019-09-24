import Hooks from 'wp-js-hooks';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import MailPoet from 'mailpoet';
import React from 'react';
import ReactDOM from 'react-dom';
import displayTutorial from './tutorial.jsx';

const renderBreadcrumb = (newsletterType) => {
  if (newsletterType !== 'wc_transactional') {
    const breadcrumbContainer = document.getElementById('mailpoet_editor_breadcrumb');
    const breadcrumb = Hooks.applyFilters(
      'mailpoet_newsletters_editor_breadcrumb',
      <Breadcrumb step="editor" />,
      newsletterType,
      'editor'
    );

    ReactDOM.render(breadcrumb, breadcrumbContainer);
  }
};

const initializeEditor = (config) => {
  const editorContainer = document.getElementById('mailpoet_editor');
  const getUrlParam = (param) => (document.location.search.split(`${param}=`)[1] || '').split('&')[0];

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
    .always(() => displayTutorial())
    .done((response) => {
      const newsletter = response.data;

      Promise.resolve(Hooks.applyFilters('mailpoet_newsletters_editor_extend_config', config, newsletter)).then((extendedConfig) => {
        window.EditorApplication.start({
          newsletter,
          config: extendedConfig,
        });
      }).catch(() => {
        window.EditorApplication.start({
          newsletter,
          config,
        });
      });

      renderBreadcrumb(newsletter.type);

      if (newsletter.status === 'sending' && newsletter.queue && newsletter.queue.status === null) {
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'sending_queue',
          action: 'pause',
          data: {
            newsletter_id: newsletter.id,
          },
        })
          .done(() => MailPoet.Notice.success(MailPoet.I18n.t('newsletterIsPaused')))
          .fail((pauseFailResponse) => {
            if (pauseFailResponse.errors.length > 0) {
              MailPoet.Notice.error(
                pauseFailResponse.errors.map((error) => error.message),
                { scroll: true, static: true }
              );
            }
          });
      }
    })
    .fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true, static: true }
        );
      }
    });
};

Hooks.addAction('mailpoet_newsletters_editor_initialize', 'mailpoet', initializeEditor);
