export interface CaptchaChallenge {
  captcha_session_id: string;
  captcha_image_url: string;
  captcha_audio_url: string;
}

/**
 * Points the CAPTCHA fields of a form at a challenge sent by the server.
 */
export function applyCaptchaChallenge(
  form: HTMLFormElement,
  meta: CaptchaChallenge,
): void {
  const sessionInput = form.querySelector<HTMLInputElement>(
    'input[name="data[captcha_session_id]"]',
  );
  if (sessionInput) {
    sessionInput.value = meta.captcha_session_id;
  }

  const image = form.querySelector<HTMLImageElement>('img.mailpoet_captcha');
  if (image) {
    image.setAttribute('src', meta.captcha_image_url);
  }

  const audio = form.querySelector<HTMLAudioElement>(
    '.mailpoet_captcha_player',
  );
  const audioSource = audio && audio.querySelector('source');
  if (audio && audioSource) {
    audioSource.setAttribute('src', meta.captcha_audio_url);
    audio.load();
  }

  const answerInput = form.querySelector<HTMLInputElement>(
    'input[name="data[captcha]"]',
  );
  if (answerInput) {
    answerInput.value = '';
  }
}
