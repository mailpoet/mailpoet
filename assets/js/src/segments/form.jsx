define(
  [
    'react',
    'mailpoet',
    'form/form.jsx'
  ],
  function(
    React,
    MailPoet,
    Form
  ) {

    let fields = [
      {
        name: 'name',
        label: MailPoet.I18n.t('name'),
        type: 'text'
      },
      {
        name: 'description',
        label: MailPoet.I18n.t('description'),
        type: 'textarea',
        tip: MailPoet.I18n.t('segmentDescriptionTip')
      }
    ];

    const messages = {
      onUpdate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('segmentUpdated'));
      },
      onCreate: function() {
        MailPoet.Notice.success(MailPoet.I18n.t('segmentAdded'));
      }
    };

    const SegmentForm = React.createClass({
      render: function() {
        return (
          <div>
            <h2 className="title">
              {MailPoet.I18n.t('segment')}
            </h2>

            <Form
              endpoint="segments"
              fields={ fields }
              params={ this.props.params }
              messages={ messages }
            />
          </div>
        );
      }
    });

    return SegmentForm;
  }
);
