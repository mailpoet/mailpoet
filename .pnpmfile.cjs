function readPackage(pkg) {
  // Resolve @wordpress/* dependencies of @woocommerce packages to those used by MailPoet.
  // This avoids their duplication and downgrading due to @woocommerce pinning them to wp-6.0.
  // This should be removed once we adopt similar pinning strategy and use dependency extraction.
  // See: https://github.com/woocommerce/woocommerce/pull/37034
  if (pkg.name?.startsWith('@woocommerce/')) {
    pkg.dependencies = Object.fromEntries(
      Object.entries(pkg.dependencies).map(([name, version]) =>
        name.startsWith('@wordpress/') || name.startsWith('@types/wordpress__')
          ? [name, '*']
          : [name, version],
      ),
    );
  }
  return pkg;
}

module.exports = {
  hooks: {
    readPackage,
  },
};
