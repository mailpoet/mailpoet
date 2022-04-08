import { t, onChange, setLowercaseValue } from 'common/functions';
import Input from 'common/form/input/input';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function BounceAddress() {
  const [email, setEmail] = useSetting('bounce', 'address');

  return (
    <>
      <Label
        title={t('bounceEmail')}
        description={
          <>
            {t('yourBouncedEmails')}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/180-how-bounce-management-works-in-mailpoet-3"
              data-beacon-article="58a5a7502c7d3a576d353c78"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
          </>
        }
        htmlFor="bounce-address"
      />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          id="bounce-address"
          placeholder="bounce@mydomain.com"
          data-automation-id="bounce-address-field"
          value={email}
          onChange={onChange(setLowercaseValue(setEmail))}
        />
      </Inputs>
    </>
  );
}
