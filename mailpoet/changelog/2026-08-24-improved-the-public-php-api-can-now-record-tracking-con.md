# Type: Improved

# Description

The public PHP API can now record tracking consent. Integrations and headless stores that show their own consent checkbox can pass `tracking_consent` to `addSubscriber` or `updateSubscriber`, along with the exact wording they displayed; MailPoet stamps the method and the time itself. This covers the one place MailPoet's own consent checkboxes cannot reach, because a caller of the API is not a MailPoet-rendered form.
