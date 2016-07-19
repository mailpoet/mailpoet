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
          jQuery('.button-primary')
            .removeClass('disabled');
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
    controlCron: function(action) {
      if(jQuery('.button-primary').hasClass('disabled')) {
        return;
      }
      jQuery('.button-primary')
        .addClass('disabled');
      MailPoet.Ajax.post({
        endpoint: 'cron',
        action: action,
      })
      .done(function(response) {
        if(!response.result) {
          MailPoet.Notice.error(MailPoet.I18n.t('daemonControlError'));
        }
      }.bind(this));
    },
    render: function() {
      switch(this.state.status) {
        case 'loading':
          return(
            <div>
              {MailPoet.I18n.t('loadingDaemonStatus')}
            </div>
          );
        case 'started':
          return(
            <div>
              {MailPoet.I18n.t('cronDaemonIsRunning')}
              <br/>
              <br/>
              <a href="#" className="button-primary" onClick={this.controlCron.bind(null, 'stop')}>{MailPoet.I18n.t('stop')}</a>
            </div>
          );
        break;
        case 'starting':
        case 'stopping':
          return(
            <div>
              {MailPoet.I18n.t('cronDaemonState').replace('%$1s', this.state.status)}
            </div>
          );
        break;
        case 'stopped':
          return(
            <div>
              {MailPoet.I18n.t('cronDaemonState').replace('%$1s', this.state.status)}
              <br />
              <br />
              <a href="#" className="button-primary" onClick={this.controlCron.bind(null, 'start')}>{MailPoet.I18n.t('start')}</a>
            </div>
          );
        break;
        case 'wordpress_task_scheduler_enabled':
          return(
            <div>
              {MailPoet.I18n.t('wordpressTaskSchedulerEnabled')}
            </div>
          );
        break;
        case false:
          return(
            <div>
              {MailPoet.I18n.t('daemonNotRunning')}
              </div>
          );
        break;
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
