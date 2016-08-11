define(
  [
    'react',
    'react-dom',
    'mailpoet'
  ],
  function(
    React,
    ReactDOM,
    MailPoet
  ) {
  var CronControl = React.createClass({
    getInitialState: function() {
      return {
        status: 'loading'
      };
    },
    getCronData: function() {
      MailPoet.Ajax.post({
          endpoint: 'cron',
          action: 'getStatus'
        }).done((response) => {
          this.setState({
            status: response.data.status
          });
        }).fail((response) => {
          this.setState({
            status: false
          });
        });
    },
    componentDidMount: function() {
      if(this.isMounted()) {
        this.getCronData();
        setInterval(this.getCronData, 5000);
      }
    },
    render: function() {
      let status;

      switch(this.state.status) {
        case false:
        case 'stopping':
        case 'stopped':
          status = MailPoet.I18n.t('cronDaemonIsNotRunning');
          break;
        case 'starting':
        case 'started':
          status = MailPoet.I18n.t('cronDaemonIsRunning');
          break;
        case 'loading':
          status = MailPoet.I18n.t('loadingDaemonStatus');
          break;
      }

      return (
        <div>
          { status }
        </div>
      );
    }
  });

  const container = document.getElementById('cron_container');

  if(container) {
    ReactDOM.render(
      <CronControl />,
      container
    );
  }
});