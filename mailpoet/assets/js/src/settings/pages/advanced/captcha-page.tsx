import ReactStringReplace from 'react-string-replace';
import { __ } from '@wordpress/i18n';
import { useSetting } from 'settings/store/hooks';
import { Inputs, Label, PageSelect } from 'settings/components';

export function CaptchaPage() {
  const [captchaPage, setCaptchaPage] = useSetting(
    'subscription',
    'pages',
    'captcha',
  );

  return (
    <>
      <Label
        title={__('Built-in CAPTCHA page', 'mailpoet')}
        description={
          <>
            {ReactStringReplace(
              __(
                'Built-in CAPTCHA is shown when users need to verify they’re not a robot. You can customize this page by editing it in WordPress and using the [mailpoet_page] shortcode to display the CAPTCHA form.',
                'mailpoet',
              ),
              '[mailpoet_page]',
              () => (
                <code key="mp">[mailpoet_page]</code>
              ),
            )}
          </>
        }
        htmlFor="subscription-pages-captcha"
      />
      <Inputs>
        <PageSelect
          value={captchaPage}
          preview="captcha"
          setValue={setCaptchaPage}
          id="subscription-pages-captcha"
        />
      </Inputs>
    </>
  );
}
