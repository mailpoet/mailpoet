import ReactDOM from 'react-dom';
import React from 'react';
import Announcement from './new_subscriber_announcement.jsx';

const container = document.getElementById('new_subscriber_announcement');

if (container) {
  ReactDOM.render(
    <Announcement
      installedAt={window.mailpoet_installed_at}
      imageUrl={window.mailpoet_new_subscriber_announcement_image}
    />, container
  );
}
