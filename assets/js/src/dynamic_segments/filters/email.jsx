import MailPoet from 'mailpoet';

const loadedLinks = {};

function loadLinks(formItems) {
  if (formItems.action !== 'clicked' && formItems.action !== 'notClicked') return Promise.resolve();
  if (!formItems.newsletter_id) return Promise.resolve();
  if (loadedLinks[formItems.newsletter_id] !== undefined) {
    return Promise.resolve(loadedLinks[formItems.newsletter_id]);
  }

  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletter_links',
    action: 'get',
    data: {
      newsletterId: formItems.newsletter_id,
    },
  })
    .then((response) => {
      const { data } = response;
      loadedLinks[formItems.newsletter_id] = data;
      return data;
    })
    .fail((response) => {
      MailPoet.Notice.error(
        response.errors.map((error) => error.message),
        { scroll: true }
      );
    });
}

export default (formItems) => loadLinks(formItems).then((links) => {
  const basicFields = [
    {
      name: 'action',
      type: 'select',
      values: {
        '': MailPoet.I18n.t('selectActionPlaceholder'),
        opened: MailPoet.I18n.t('emailActionOpened'),
        notOpened: MailPoet.I18n.t('emailActionNotOpened'),
        clicked: MailPoet.I18n.t('emailActionClicked'),
        notClicked: MailPoet.I18n.t('emailActionNotClicked'),
      },
    },
    {
      name: 'newsletter_id',
      type: 'selection',
      resetSelect2OnUpdate: true,
      endpoint: 'newsletters_list',
      placeholder: MailPoet.I18n.t('selectNewsletterPlaceholder'),
      forceSelect2: true,
      getLabel: (newsletter) => {
        const sentAt = (newsletter.sent_at) ? MailPoet.Date.format(newsletter.sent_at) : MailPoet.I18n.t('notSentYet');
        return `${newsletter.subject} (${sentAt})`;
      },
    },
  ];
  if (links) {
    return [...basicFields, {
      name: 'link_id',
      type: 'selection',
      placeholder: MailPoet.I18n.t('selectLinkPlaceholder'),
      forceSelect2: true,
      getLabel: (link) => link.url,
      values: links,
    }];
  }
  return basicFields;
});
