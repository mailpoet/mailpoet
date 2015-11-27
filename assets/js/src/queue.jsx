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
   var QueueDaemonControl = React.createClass({
     getInitialState: function () {
       return (queueDaemon) ? {
         status: queueDaemon.status,
         timeSinceStart: queueDaemon.time_since_start,
         timeSinceUpdate: queueDaemon.time_since_update,
         counter: queueDaemon.counter
       } : null;
     },
     getDaemonData: function () {
       MailPoet.Ajax.post({
         endpoint: 'queue',
         action: 'getQueueStatus'
       }).done(function (response) {
         this.setState({
           status: response.status,
           timeSinceStart: response.time_since_start,
           timeSinceUpdate: response.time_since_update,
           counter: response.counter,
         });
       }.bind(this));
     },
     componentDidMount: function () {
       this.getDaemonData;
       setInterval(this.getDaemonData, 5000);
     },
     render: function () {
       if (!this.state) {
         return (
          <div className="QueueControl">
            Woops, daemon is not running ;\
          </div>
         )
       }
       return (
        <div>
          <div>
            Queue is currently <b>{this.state.status}</b>.
            <br/>
            <br/>
            It was started
            <b> {this.state.timeSinceStart} </b> and was last executed
            <b> {this.state.timeSinceUpdate} </b> for a total of
            <b> {this.state.counter} </b> times (once every 30 seconds, unless it was interrupted and restarted).
            <br />
          </div>
          <div>

          </div>
        </div>
       );
     }
   });
   let container = document.getElementById('queue_container');
   if (container) {
     ReactDOM.render(
      <QueueDaemonControl />,
      container
     )
   }
 }
);