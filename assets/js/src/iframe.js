define('iframe', ['mailpoet', 'jquery'], function(MailPoet, jQuery) {
  'use strict';
  MailPoet.Iframe = {
    marginY: 15,
    autoSize: function(iframe) {
      if(!iframe) return;

      this.setSize(
        iframe,
        iframe.contentWindow.document.body.scrollHeight
      );
    },
    setSize: function(iframe, i) {
      if(!iframe) return;

      iframe.style.height = (
        parseInt(i) + this.marginY
      ) + "px";
    }
  };

  return MailPoet;
});
