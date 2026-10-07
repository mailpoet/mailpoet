import { JSDOM } from 'jsdom';
import { applyCaptchaChallenge } from '../../../assets/js/src/form/captcha-session';

const formMarkup = `
  <form>
    <input type="hidden" name="data[captcha_session_id]" value="old-session" />
    <img class="mailpoet_captcha" src="https://example.com/old.png" />
    <audio class="mailpoet_captcha_player">
      <source src="https://example.com/old.wav" type="audio/wav" />
    </audio>
    <input type="text" name="data[captcha]" value="typed answer" />
  </form>
`;

const meta = {
  captcha_session_id: 'new-session',
  captcha_image_url: 'https://example.com/new.png',
  captcha_audio_url: 'https://example.com/new.wav',
};

describe('applyCaptchaChallenge', () => {
  let dom: JSDOM;
  let form: HTMLFormElement;
  let loadCalls: number;

  beforeEach(() => {
    dom = new JSDOM(`<!doctype html><body>${formMarkup}</body>`);
    form = dom.window.document.querySelector('form');
    loadCalls = 0;
    const audio = form.querySelector('audio');
    audio.load = () => {
      loadCalls += 1;
    };
    applyCaptchaChallenge(form, meta);
  });

  afterEach(() => {
    dom.window.close();
  });

  it('stores the new session id', () => {
    const input = form.querySelector<HTMLInputElement>(
      'input[name="data[captcha_session_id]"]',
    );
    expect(input.value).to.equal('new-session');
  });

  it('points the image at the new challenge', () => {
    expect(
      form.querySelector('img.mailpoet_captcha').getAttribute('src'),
    ).to.match(/^https:\/\/example\.com\/new\.png\?cachebust=\d+$/);
  });

  it('points the audio source at the new challenge', () => {
    expect(
      form.querySelector('.mailpoet_captcha_player source').getAttribute('src'),
    ).to.match(/^https:\/\/example\.com\/new\.wav\?cachebust=\d+$/);
  });

  it('reloads the audio element', () => {
    expect(loadCalls).to.equal(1);
  });

  describe('when the same challenge is applied twice', () => {
    const metaWithQuery = {
      captcha_session_id: 'same-session',
      captcha_image_url: 'https://example.com/captcha.php?data=abc&type=image',
      captcha_audio_url: 'https://example.com/captcha.php?data=abc&type=audio',
    };
    const imageSrc = () =>
      form.querySelector('img.mailpoet_captcha').getAttribute('src');
    const audioSrc = () =>
      form.querySelector('.mailpoet_captcha_player source').getAttribute('src');

    it('changes the image src and keeps the original parameters', () => {
      applyCaptchaChallenge(form, metaWithQuery);
      const first = imageSrc();
      applyCaptchaChallenge(form, metaWithQuery);
      const second = imageSrc();
      expect(second).to.not.equal(first);
      [first, second].forEach((src) => {
        const url = new URL(src);
        expect(url.searchParams.get('data')).to.equal('abc');
        expect(url.searchParams.get('type')).to.equal('image');
        expect(url.searchParams.get('cachebust')).to.not.equal(null);
      });
    });

    it('changes the audio src and keeps the original parameters', () => {
      applyCaptchaChallenge(form, metaWithQuery);
      const first = audioSrc();
      applyCaptchaChallenge(form, metaWithQuery);
      const second = audioSrc();
      expect(second).to.not.equal(first);
      [first, second].forEach((src) => {
        const url = new URL(src);
        expect(url.searchParams.get('data')).to.equal('abc');
        expect(url.searchParams.get('type')).to.equal('audio');
        expect(url.searchParams.get('cachebust')).to.not.equal(null);
      });
    });

    it('adds a query string when the URL has none', () => {
      applyCaptchaChallenge(form, meta);
      expect(imageSrc()).to.match(
        /^https:\/\/example\.com\/new\.png\?cachebust=\d+$/,
      );
      expect(audioSrc()).to.match(
        /^https:\/\/example\.com\/new\.wav\?cachebust=\d+$/,
      );
    });
  });

  it('clears the typed answer', () => {
    const input = form.querySelector<HTMLInputElement>(
      'input[name="data[captcha]"]',
    );
    expect(input.value).to.equal('');
  });
});
