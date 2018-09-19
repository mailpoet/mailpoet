import Hooks from 'wp-js-hooks';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import InAppAnnouncement from 'in_app_announcements/in_app_announcement.jsx';
import MailPoet from 'mailpoet';
import React from 'react';
import ReactDOM from 'react-dom';
import moment from 'moment';

const renderBreadcrumb = (newsletterType) => {
  const breadcrumbContainer = document.getElementById('mailpoet_editor_breadcrumb');
  const breadcrumb = Hooks.applyFilters(
    'mailpoet_newsletters_editor_breadcrumb',
    <Breadcrumb step="editor" />,
    newsletterType,
    'editor'
  );

  ReactDOM.render(breadcrumb, breadcrumbContainer);
};

const renderAnnouncement = () => {
  const container = document.getElementById('mailpoet_editor_announcement');
  const heading = MailPoet.I18n.t('announcementBackgroundImagesHeading')
    .replace('%username%', window.config.currentUserFirstName || window.config.currentUserUsername);
  ReactDOM.render(
    <InAppAnnouncement
      validUntil={new Date('2018-10-06')}
      height="700px"
      showOnlyOnceSlug="background_image"
    >
      <div className="mailpoet_in_app_announcement_background_videos">
        <h2>{heading}</h2>
        <p>{MailPoet.I18n.t('announcementBackgroundImagesMessage')}</p>
        <video src={window.config.backgroundImageDemoUrl} controls autoPlay><track kind="captions" /></video>
      </div>
    </InAppAnnouncement>,
    container);
};

function displayTutorial() {
  const key = `user_seen_editor_tutorial${window.config.currentUserId}`;
  if (window.config.dragDemoUrlSettings) {
    return;
  }
  if (moment(window.config.installedAt).isBefore(moment().subtract(7, 'days'))) {
    return;
  }
  MailPoet.Modal.popup({
    title: MailPoet.I18n.t('tutorialVideoTitle'),
    template: `<video style="height:640px;" src="${window.config.dragDemoUrl}" controls autoplay></video>`,
    onCancel: () => {
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'settings',
        action: 'set',
        data: { [key]: 1 },
      });
    },
  });
}

const initializeEditor = (config) => {
  const editorContainer = document.getElementById('mailpoet_editor');
  const getUrlParam = param => (location.search.split(`${param}=`)[1] || '').split('&')[0];

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
      renderAnnouncement();

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
