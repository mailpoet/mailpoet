export function FooterCredit({ logoSrc }: { logoSrc: string }) {
  return (
    <img
      className="mailpoet-email-footer-credit"
      src={logoSrc}
      alt="MailPoet"
    />
  );
}

export default FooterCredit;
