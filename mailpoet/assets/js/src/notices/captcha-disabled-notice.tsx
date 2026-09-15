import { __ } from '@wordpress/i18n';
import { Notice } from 'notices/notice';

type Props = {
  captchaDisabled: boolean;
  mssActive: boolean;
};

function CaptchaDisabledNotice({ captchaDisabled, mssActive }: Props) {
  if (!captchaDisabled) return null;
  if (!mssActive) return null;

  return (
    <Notice type="info" timeout={false} closable={false} renderInPlace>
      <p>
        {__(
          'CAPTCHA helps protect your forms from spam and abuse. If bot activity is detected, form sending may be restricted until protection is enabled. If other effective anti-spam measures are in place, this notice can be ignored.',
          'mailpoet',
        )}{' '}
        <a href="?page=mailpoet-settings#/advanced">
          {
            // translators: A link that leads to the CAPTCHA settings
            __('Enable CAPTCHA', 'mailpoet')
          }
        </a>
      </p>
    </Notice>
  );
}

CaptchaDisabledNotice.displayName = 'CaptchaDisabledNotice';
export { CaptchaDisabledNotice };
