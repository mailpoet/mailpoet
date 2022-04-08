import _ from 'underscore';
import MailPoet from 'mailpoet';
import html2canvas from 'html2canvas';

/**
 * Generates a thumbnail from a HTML element.
 *
 * @param  {HTMLElement}      element
 * @return {Promise<String>} DataURL of the generated image.
 */
export const fromDom = async (element: HTMLElement) => {
  const canvas = await html2canvas(element, {
    logging: false,
    scale: 1, // Use a constant scale to prevent generating large images on Retina displays
  });
  return canvas.toDataURL('image/jpeg');
};

interface ThumbnailIframe extends HTMLIFrameElement {
  onError?: () => void;
}

/**
 * Generates a thumbnail from an URL.
 *
 * @param  {String}        url
 * @return {Promise<String>} DataURL of the generated image.
 */
export const fromUrl = (url) =>
  new Promise((resolve, reject) => {
    const iframe: ThumbnailIframe = document.createElement('iframe');
    const protocol = document.location.href.startsWith('https://')
      ? 'https:'
      : 'http:';
    iframe.src = `${protocol}${url.replace(/^https?:/, '')}`;
    iframe.style.opacity = '0';
    iframe.scrolling = 'no';
    iframe.onload = async () => {
      const container = iframe.contentDocument.documentElement;
      container.style.padding = '10px 20px';
      try {
        const image = await fromDom(container);
        document.body.removeChild(iframe);
        resolve(image);
      } catch (err) {
        document.body.removeChild(iframe);
        reject(MailPoet.I18n.t('errorWhileTakingScreenshot'));
      }
    };
    const onError = () => {
      document.body.removeChild(iframe);
      reject(MailPoet.I18n.t('errorWhileTakingScreenshot'));
    };
    iframe.onerror = onError;
    iframe.onError = onError;
    iframe.className = 'mailpoet-template-iframe';
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
export const fromNewsletter = (data) =>
  new Promise((resolve, reject) => {
    const json = data;
    if (!_.isUndefined(json.body)) {
      json.body = JSON.stringify(json.body);
    }
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'newsletters',
      action: 'showPreview',
      data: json,
    })
      .done((response) => {
        void fromUrl(response.meta.preview_url).then(resolve).catch(reject);
      })
      .fail((response) => reject(response.errors));
  });
