define('iframe', ['mailpoet'], function iframeModule(mp) {
  'use strict';

  var MailPoet = mp;
  MailPoet.Iframe = {
    marginY: 20,
    autoSize: function autoSize(iframe) {
      if (!iframe) return;

      this.setSize(
        iframe,
        iframe.contentWindow.document.body.scrollHeight
      );
    },
    setSize: function setSize(sizeIframe, i) {
      var iframe = sizeIframe;
      if (!iframe) return;

      iframe.style.height = (
        parseInt(i, 10) + this.marginY
      ) + 'px';
    }
  };

  return MailPoet;
});
