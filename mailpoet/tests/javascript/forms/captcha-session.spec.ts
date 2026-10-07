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
    ).to.equal('https://example.com/new.png');
  });

  it('points the audio source at the new challenge', () => {
    expect(
      form.querySelector('.mailpoet_captcha_player source').getAttribute('src'),
    ).to.equal('https://example.com/new.wav');
  });

  it('reloads the audio element', () => {
    expect(loadCalls).to.equal(1);
  });

  it('clears the typed answer', () => {
    const input = form.querySelector<HTMLInputElement>(
      'input[name="data[captcha]"]',
    );
    expect(input.value).to.equal('');
  });
});
