import _ from 'underscore';
import MailPoet from 'mailpoet';
import html2canvas from 'html2canvas';

/**
 * Generates a thumbnail from a DOM element.
 *
 * @param  {DOMElement}      element
 * @return {Promise<String>} DataURL of the generated image.
 */
export const fromDom = element =>
  html2canvas(element, {
    allowTaint: true,
    useCORS: true,
    foreignObjectRendering: true,
    logging: false,
  }).then(canvas => canvas.toDataURL('image/jpeg'));

/**
 * Generates a thumbnail from an URL.
 *
 * @param  {String}        url
 * @return {Promise<String>} DataURL of the generated image.
 */
export const fromUrl = url =>
  new Promise((resolve, reject) => {
    const iframe = document.createElement('iframe');
    const protocol = location.href.startsWith('https://') ? 'https' : 'http';
    iframe.src = protocol + url.replace(/^https?/, '');
    iframe.style.opacity = 0;
    iframe.onload = () => {
      fromDom(iframe.contentDocument.documentElement)
      .then((image) => {
        document.body.removeChild(iframe);
        resolve(image);
      })
      .catch(() => {
        document.body.removeChild(iframe);
        reject(MailPoet.I18n.t('errorWhileTakingScreenshot'));
      });
    };
    const onError = () => {
      document.body.removeChild(iframe);
      reject(MailPoet.I18n.t('errorWhileTakingScreenshot'));
    };
    iframe.onerror = onError;
    iframe.onError = onError;
    iframe.className = 'mailpoet_template_iframe';
    try {
      document.body.appendChild(iframe);
    } catch (err) {
      onError();
    }
  });

/**
 * Generates a thumbnail from a newsletter's data.
 *
 * @param  {Object}        data
 * @return {Promise<String>} DataURL of the generated image.
 */
export const fromNewsletter = data =>
  new Promise((resolve, reject) => {
    const json = data;
    if (!_.isUndefined(json.body)) {
      json.body = JSON.stringify(json.body);
    }
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'showPreview',
      data: json,
    }).done(response => fromUrl(response.meta.preview_url)
        .then(resolve)
        .catch(reject)
    ).fail(response => reject(response.errors));
  });

