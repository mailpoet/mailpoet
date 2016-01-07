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
          MailPoet.Notice.error(MailPoetI18n.daemonControlError);
        }
      }.bind(this));
    },
    render: function() {
      if(this.state.status === 'loading') {
        return(<div>Loading daemon status...</div>);
      }
      switch(this.state.status) {
        case 'started':
          return(
            <div>
              Cron daemon is running.
              <br/>
              <br/>
              It was started
              <strong> {this.state.timeSinceStart} </strong> and last executed
              <strong> {this.state.timeSinceUpdate} </strong> for a total of
              <strong> {this.state.counter} </strong> times (once every 30 seconds, unless it was interrupted and restarted).
              <br />
              <br />
              <a href="#" className="button-primary" onClick={this.controlCron.bind(null, 'stop')}>Stop</a>
            </div>
          );
        break;
        case 'starting':
        case 'stopping':
          return(
            <div>
              Daemon is {this.state.status}
            </div>
          );
        break;
        case 'stopped':
          return(
            <div>
              Daemon is {this.state.status}
              <br />
              <br />
              <a href="#" className="button-primary" onClick={this.controlCron.bind(null, 'start')}>Start</a>
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