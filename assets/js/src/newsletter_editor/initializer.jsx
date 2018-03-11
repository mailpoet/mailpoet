import Hooks from 'wp-js-hooks';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import MailPoet from 'mailpoet';
import React from 'react';
import ReactDOM from 'react-dom';

const editorContainer = document.getElementById('mailpoet_editor');

const getUrlParam = param => (location.search.split(`${param}=`)[1] || '').split('&')[0];

const renderBreadcrumb = (newsletterType) => {
  const breadcrumbContainer = document.getElementById('mailpoet_editor_breadcrumb');
  let breadcrumb = Hooks.applyFilters('mailpoet_newsletters_editor_breadcrumb', newsletterType, 'editor');
  breadcrumb = (breadcrumb !== newsletterType) ? breadcrumb : <Breadcrumb step="editor" />;

  if (breadcrumbContainer) {
    ReactDOM.render(breadcrumb, breadcrumbContainer);
  }
};

const initializeEditor = (config) => {
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
  .done((response) => {
    const newsletter = response.data;

    window.EditorApplication.start({
      newsletter: newsletter,
      config: config,
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
            pauseFailResponse.errors.map(error => error.message),
            { scroll: true, static: true }
          );
        }
      });
    }
  })
  .fail((response) => {
    if (response.errors.length > 0) {
      MailPoet.Notice.error(
        response.errors.map(error => error.message),
        { scroll: true, static: true }
      );
    }
  });
};

Hooks.addAction('mailpoet_newsletters_editor_initialize', initializeEditor);
