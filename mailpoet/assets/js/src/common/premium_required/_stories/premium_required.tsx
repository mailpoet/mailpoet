import PremiumRequired from '../premium_required';
import Button from '../../button/button';

export default {
  title: 'Premium Required',
  component: PremiumRequired,
};

export function PremiumsRequired() {
  return (
    <div>
      <PremiumRequired
        title="This is a Premium Feature"
        message={
          <p>
            Learn more about your subscribers and optimize your campaigns. See
            who opened your emails, which links they clicked, and then use the
            data to make y our emails even better. And if you run a WooCommerce
            store, you will also see the revenue earned per email.
            <a href="#">Learn more.</a>
          </p>
        }
        actionButton={<Button href="#">Sign Up</Button>}
      />
    </div>
  );
}
