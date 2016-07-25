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
        })
        .done(function(response) {
          if(response.status !== undefined) {
            this.setState(response);
          } else {
            this.replaceState();
          }
        }.bind(this));
    },
    componentDidMount: function() {
      if(this.isMounted()) {
        this.getCronData();
        setInterval(this.getCronData, 5000);
      }
    },
    render: function() {
      switch(this.state.status) {
        case 'loading':
          return(
            <div>
              {MailPoet.I18n.t('loadingDaemonStatus')}
            </div>
          );
        case false:
          return(
            <div>
              {MailPoet.I18n.t('daemonNotRunning')}
              </div>
          );
        default:
          return(
            <div>
              {MailPoet.I18n.t('cronDaemonState').replace('%$1s', this.state.status)}
            </div>
          );
      }
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