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
    getDaemonData: function() {
      MailPoet.Ajax.post({
          endpoint: 'cron',
          action: 'getDaemonStatus'
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
        this.getDaemonData();
        setInterval(this.getDaemonData, 5000);
      }
    },
    controlDaemon: function(action) {
      jQuery('.button-primary')
        .addClass('disabled');
      MailPoet.Ajax.post({
        endpoint: 'cron',
        action: 'controlDaemon',
        data: {
          'action': action
        }
      })
      .done(function(response) {
        if(!response.result) {
          //this.replaceState();
        } else {
          //this.setState(response);
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
              <a href="#" className="button-primary" onClick={this.controlDaemon.bind(null, 'stop')}>Stop</a>&nbsp;&nbsp;
              <a href="#" className="button-primary" onClick={this.controlDaemon.bind(null, 'pause')}>Pause</a>
            </div>
          );
        break;
        case 'paused':
        case 'stopped':
          return(
            <div>
              Daemon is {this.state.status}
              <br />
              <br />
              <a href="#" className="button-primary" onClick={this.controlDaemon.bind(null, 'start')}>Start</a>
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