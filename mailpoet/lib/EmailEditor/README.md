# MailPoet Plugin Email Editor Integration

This folder contains the code for the MailPoet Plugin Email Editor Integration.

**MailPoet** specific code and features should be placed here.

Documentation for the core email editor packages can be located here:

- Email Editor PHP Package: [docs-->packages/php/email-editor/README.md](../../../packages/php/email-editor/README.md)
- Email Editor JS Package: [docs-->packages/js/email-editor/README.md](../../../packages/js/email-editor/README.md)

### Integrations

- PHP Integration can be found [here-->mailpoet/lib/EmailEditor/Integrations/MailPoet](Integrations/MailPoet)
- JS Integration is subdivided.
  - Custom Gutenberg blocks should be [here-->mailpoet/assets/js/src/mailpoet-custom-email-editor-blocks](../../assets/js/src/mailpoet-custom-email-editor-blocks)
  - MailPoet extended integration (i.e., using MailPoet components within the email editor) should be [here-->mailpoet/assets/js/src/mailpoet-email-editor-integration](../../assets/js/src/mailpoet-email-editor-integration)

## FAQ

1. How can I activate the new email editor?
   - You need to active it as an experimental feature on the URL {your-website}/wp-admin/admin.php?page=mailpoet-experimental
   - Afterward, you can see a dropdown button when you want to create a new email.
2. Are emails from the old editor compatible?
   - No. Unfortunately, email editors are not compatible at this moment, and there is no tool which would allow you to migrate old emails yet.
3. Which WP versions are supported?
   - The new email editor supports only the latest WP version.
4. Can I use the latest Gutenberg version?
   - You can use the latest version by installing the Gutenberg plugin, but there is a high chance that the editor will not work properly.
