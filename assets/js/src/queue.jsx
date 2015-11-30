define(
 [
   'react',
   'react-dom',
   'mailpoet',
   'classnames'
 ],
 function (
  React,
  ReactDOM,
  MailPoet,
  classNames
 ) {
   var QueueControl = React.createClass({
     getInitialState: function () {
       return (queueDaemon) ? queueDaemon : null;
     },
     getDaemonData: function () {
       MailPoet.Ajax.post({
         endpoint: 'queue',
         action: 'getDaemonStatus'
       }).done(function (response) {
         jQuery('.button-primary').removeClass('disabled');
         if (!response) {
           this.replaceState();
         } else {
           this.setState(response);
         }
       }.bind(this));
     },
     componentDidMount: function componentDidMount() {
       if (this.isMounted()) {
         this.getDaemonData;
         setInterval(this.getDaemonData, 5000);
       }
     },
     controlDaemon: function (action) {
       jQuery('.button-primary').addClass('disabled');
       MailPoet.Ajax.post({
         endpoint: 'queue',
         action: 'controlDaemon',
         data: {'action': action}
       }).done(function (response) {
         if (!response) {
           this.replaceState();
         } else {
           this.setState(response);
         }
       }.bind(this));
     },
     render: function () {
       if (!this.state) {
         return
         <div>
           Woops, daemon is not running ;\
         </div>
       }
       switch (this.state.status) {
         case 'started':
           return (
            <div>
              <div>
                Queue daemon is running.
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
            </div>
           );
           break;
         case 'paused':
         case 'stopped':
           return (
            <div>
              Daemon is {this.state.status}
              <br />
              <br />
              <a href="#" className="button-primary" onClick={this.controlDaemon.bind(null, 'start')}>Start</a>
            </div>
           )
           break;
       }
     }
   });
   let container = document.getElementById('queue_container');
   if (container) {
     ReactDOM.render(
      <QueueControl />,
      document.getElementById('queue_status')
     )
   }
 }
);