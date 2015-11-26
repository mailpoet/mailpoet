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
       return (queueDaemon) ? {status: queueDaemon.status} : null;
     },
     getDaemonData: function () {
       MailPoet.Ajax.post({
         endpoint: 'queue',
         action: 'getQueueStatus'
       }).done(function (response) {
         this.setState({status: response.status});
       }.bind(this));
     },
     componentDidMount: function () {
       this.getDaemonData;
       setInterval(this.getDaemonData, 2000);
     },
     render: function () {
       if (!this.state) {
         return (
          <div className="QueueControl">
            If you're seeing this message, queue daemon has not even been created!
          </div>
         )
       }
       return (
        <div>
          Queue is currently <b>{this.state.status}</b>
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