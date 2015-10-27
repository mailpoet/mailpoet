define(
  [
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Form
  ) {

    var fields = [
      {
        name: 'name',
        label: 'Name',
        type: 'text'
      },
      {
        name: 'description',
        label: 'Description',
        type: 'textarea'
      }
    ];

    var messages = {
      updated: function() {
        MailPoet.Notice.success('Segment successfully updated!');
      },
      created: function() {
        MailPoet.Notice.success('Segment successfully added!');
      }
    };

    var Link = Router.Link;

    var SegmentForm = React.createClass({
      render: function() {
        return (
          <div>
            <h2 className="title">
              Segment <Link className="add-new-h2" to="/">Back to list</Link>
            </h2>

            <Form
              endpoint="segments"
              fields={ fields }
              params={ this.props.params }
              messages={ messages } />
          </div>
        );
      }
    });

    return SegmentForm;
  }
);
