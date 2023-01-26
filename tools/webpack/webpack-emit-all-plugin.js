import { basename, dirname, join } from 'node:path';

/**
 * Webpack plugin to emit all transpiled modules as individual files without bundling.
 * This is useful for libraries, making them easier to consume and tree shake.
 *
 * Gutenberg solves this by custom scripting around Babel (which is rather complex).
 * WooCommerce uses TypeScript (which doesn't work with Webpack plugins).
 *
 * See:
 *   https://github.com/webpack/webpack/issues/5866
 *   https://github.com/webpack/webpack/issues/11230
 *
 * Inspired by: https://github.com/DrewML/webpack-emit-all-plugin
 */
export class WebpackEmitAllPlugin {
  constructor(opts = {}) {
    this.context = opts.context;
    this.ignorePattern = opts.ignorePattern || /node_modules/;
  }

  apply(compiler) {
    compiler.hooks.environment.tap('EmitAllPlugin', (args) => {
      compiler.options.optimization = {
        ...compiler.options.optimization,
        minimize: false,
        minimizer: [],
        concatenateModules: false,
        splitChunks: false,
        realContentHash: false,
        removeEmptyChunks: false,
      };
    });

    compiler.hooks.compilation.tap('EmitAllPlugin', (compilation) => {
      compilation.hooks.processAssets.tap(
        {
          name: 'EmitAllPlugin',
          stage: compilation.PROCESS_ASSETS_STAGE_ADDITIONS,
          additionalAssets: true,
        },
        () => {
          Object.keys(compilation.assets).forEach((asset) => {
            if (asset.endsWith('.js')) {
              compilation.deleteAsset(asset);
            }
          });
        },
      );
    });

    compiler.hooks.afterEmit.tapPromise('EmitAllPlugin', (compilation) =>
      Promise.all(
        Array.from(compilation.modules).map((module) =>
          this.#processModule(compiler, module),
        ),
      ),
    );
  }

  async #processModule(compiler, module) {
    const fs = compiler.outputFileSystem.promises;
    const outputPath = compiler.options.output.path;
    const originalSource = module.originalSource();
    if (!originalSource || this.ignorePattern.test(module.resource)) {
      return;
    }

    const path = module.resource.replace(this.context ?? compiler.context, '');
    const dest = join(outputPath, path).replace(/\.[jt]sx?$/i, '.js');
    const { source, map } = originalSource.sourceAndMap();
    const suffix = map ? `\n//# sourceMappingURL=${basename(dest)}.map` : '';

    await fs.mkdir(dirname(dest), { recursive: true });
    return Promise.all([
      fs.writeFile(dest, `${source}${suffix}`),
      map ? fs.writeFile(`${dest}.map`, JSON.stringify(map)) : undefined,
    ]);
  }
}
