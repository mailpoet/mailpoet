export interface CaptchaChallenge {
  captcha_session_id: string;
  captcha_image_url: string;
  captcha_audio_url: string;
}

let lastCachebust = 0;

// Strictly increasing, so two calls in the same millisecond still differ.
function nextCachebust(): number {
  lastCachebust = Math.max(Date.now(), lastCachebust + 1);
  return lastCachebust;
}

function withCachebust(url: string, cachebust: number): string {
  return `${url}${url.includes('?') ? '&' : '?'}cachebust=${cachebust}`;
}

/**
 * Points the CAPTCHA fields of a form at a challenge sent by the server.
 */
export function applyCaptchaChallenge(
  form: HTMLFormElement,
  meta: CaptchaChallenge,
): void {
  const cachebust = nextCachebust();
  const sessionInput = form.querySelector<HTMLInputElement>(
    'input[name="data[captcha_session_id]"]',
  );
  if (sessionInput) {
    sessionInput.value = meta.captcha_session_id;
  }

  const image = form.querySelector<HTMLImageElement>('img.mailpoet_captcha');
  if (image) {
    image.setAttribute('src', withCachebust(meta.captcha_image_url, cachebust));
  }

  const audio = form.querySelector<HTMLAudioElement>(
    '.mailpoet_captcha_player',
  );
  const audioSource = audio && audio.querySelector('source');
  if (audio && audioSource) {
    audioSource.setAttribute(
      'src',
      withCachebust(meta.captcha_audio_url, cachebust),
    );
    audio.load();
  }

  const answerInput = form.querySelector<HTMLInputElement>(
    'input[name="data[captcha]"]',
  );
  if (answerInput) {
    answerInput.value = '';
  }
}
