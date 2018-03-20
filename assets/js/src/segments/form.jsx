define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
  ],
  (
    React,
    Router,
    MailPoet,
    Form
  ) => {
    const fields = [
      {
        name: 'name',
        label: MailPoet.I18n.t('name'),
        type: 'text',
      },
      {
        name: 'description',
        label: MailPoet.I18n.t('description'),
        type: 'textarea',
        tip: MailPoet.I18n.t('segmentDescriptionTip'),
      },
    ];

    const messages = {
      onUpdate: function onUpdate() {
        MailPoet.Notice.success(MailPoet.I18n.t('segmentUpdated'));
      },
      onCreate: function onCreate() {
        MailPoet.Notice.success(MailPoet.I18n.t('segmentAdded'));
        MailPoet.trackEvent('Lists > Add new', {
          'MailPoet Free version': window.mailpoet_version,
        });
      },
    };

    const Link = Router.Link;

    const SegmentForm = React.createClass({
      render: function render() {
        return (
          <div>
            <h1 className="title">
              {MailPoet.I18n.t('segment')}
              <Link className="page-title-action" to="/">{MailPoet.I18n.t('backToList')}</Link>
            </h1>

            <Form
              endpoint="segments"
              fields={fields}
              params={this.props.params}
              messages={messages}
            />
          </div>
        );
      },
    });

    return SegmentForm;
  }
);
