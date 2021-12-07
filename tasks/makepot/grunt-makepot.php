<?php
require_once( dirname( __FILE__ ) . '/makepot.php' );

/**
 * POT generation methods for grunt-wp-i18n.
 */
class GruntMakePOT extends MakePOT {
	/**
	 * Valid project types.
	 *
	 * @type array
	 */
	public $projects = array(
		'wp-plugin',
		'wp-theme',
	);

	/**
	 * Generate a POT file for a plugin.
	 *
	 * @param string $dir Directory to search for gettext calls.
	 * @param string $output POT file name.
	 * @param string $slug Optional. Plugin slug.
	 * @param string $excludes Optional. Comma-separated list of exclusion patterns.
	 * @param string $includes Optional. Comma-separated list of inclusion patterns.
	 * @return bool
	 */
	public function wp_plugin( $dir, $output, $slug = null, $excludes = '', $includes = '' ) {
		if ( is_null( $slug ) ) {
			// Guess plugin slug.
			$slug = $this->guess_plugin_slug( $dir );
		}

		$main_file = $dir . '/' . $slug . '.php';
		$source = $this->get_first_lines( $main_file, $this->max_header_lines );
		$excludes = $this->normalize_patterns( $excludes );
		$includes = $this->normalize_patterns( $includes );

		$output = is_null( $output )? "$slug.pot" : $output;

		$placeholders = array();
		$placeholders['version'] = $this->get_addon_header( 'Version', $source );
		$placeholders['author'] = $this->get_addon_header( 'Author', $source );
		$placeholders['name'] = $this->get_addon_header( 'Plugin Name', $source );
		$placeholders['slug'] = $slug;

		/*$license = $this->get_addon_header( 'License', $source );
		if ( $license ) {
			$this->meta['wp-plugin']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the {$license}.=!>";
		} else {
			$this->meta['wp-plugin']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.=!>";
		}*/

		$result = $this->xgettext( 'wp-plugin', $dir, $output, $placeholders, $excludes, $includes );
		if ( ! $result ) {
			return false;
		}

		$potextmeta = new PotExtMeta;
		$result = $potextmeta->append( $main_file, $output );
		return $result;
	}

	/**
	 * Generate a POT file for a theme.
	 *
	 * @param string $dir Directory to search for gettext calls.
	 * @param string $output POT file name.
	 * @param string $slug Optional. Theme slug.
	 * @param string $excludes Optional. Comma-separated list of exclusion patterns.
	 * @param string $includes Optional. Comma-separated list of inclusion patterns.
	 * @return bool
	 */
	public function wp_theme( $dir, $output, $slug = null, $excludes = '', $includes = '' ) {
		if ( is_null( $slug ) ) {
			// Guess theme slug.
			$slug = $this->guess_plugin_slug( $dir );
		}

		$main_file = $dir . '/style.css';
		$source = $this->get_first_lines( $main_file, $this->max_header_lines );
		$excludes = $this->normalize_patterns( $excludes );
		$includes = $this->normalize_patterns( $includes );
		$output = is_null( $output )? "$slug.pot" : $output;

		$placeholders = array();
		$placeholders['version'] = $this->get_addon_header( 'Version', $source );
		$placeholders['author'] = $this->get_addon_header( 'Author', $source );
		$placeholders['name'] = $this->get_addon_header( 'Theme Name', $source );
		$placeholders['slug'] = $slug;

		$license = $this->get_addon_header( 'License', $source );
		if ( $license ) {
			$this->meta['wp-theme']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the {$license}.=!>";
		} else {
			$this->meta['wp-theme']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.=!>";
		}

		$result = $this->xgettext( 'wp-theme', $dir, $output, $placeholders, $excludes, $includes );
		if ( ! $result ) {
			return false;
		}

		$potextmeta = new PotExtMeta;
		$result = $potextmeta->append( $main_file, $output, array( 'Theme Name', 'Theme URI', 'Description', 'Author', 'Author URI' ) );
		if ( ! $result ) {
			return false;
		}

		// If we're dealing with a pre-3.4 default theme, don't extract page templates before 3.4.
		$extract_templates = ! in_array( $slug, array( 'twentyten', 'twentyeleven', 'default', 'classic' ) );
		if ( ! $extract_templates ) {
			$wp_dir = dirname( dirname( dirname( $dir ) ) );
			$extract_templates = file_exists( "$wp_dir/wp-admin/user/about.php" ) || ! file_exists( "$wp_dir/wp-load.php" );
		}

		if ( $extract_templates ) {
			$result = $potextmeta->append( $dir, $output, array( 'Template Name' ) );
			if ( ! $result ) {
				return false;
			}

			$files = scandir( $dir );
			foreach ( $files as $file ) {
				if ( in_array( $file, array( '.', '..', '.git', 'CVS', 'node_modules' ) ) ) {
					continue;
				}

				if ( is_dir( $dir . '/' . $file ) ) {
					$result = $potextmeta->append( $dir . '/' . $file, $output, array( 'Template Name' ) );
					if ( ! $result ) {
						return false;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Convert a string or array of exclusion/inclusion patterns into an array.
	 *
	 * @param string|array $patterns Comma-separated string or array of exclusion/inclusion patterns.
	 * @return array
	 */
	protected function normalize_patterns( $patterns ) {
		if ( is_string( $patterns ) ) {
			$patterns = explode( ',', $patterns );
		}

		// Remove empty items and non-strings.
		return array_filter( array_filter( (array) $patterns ), 'is_string' );
	}
}

/**
 * CLI interface.
 *
 * Run the CLI only if the file wasn't included.
 */
$included_files = get_included_files();
if ( __FILE__ == $included_files[0] ) {
	$makepot = new GruntMakePOT;
	$method = str_replace( '-', '_', $argv[1] );

	if ( in_array( count( $argv ), range( 3, 7 ) ) && in_array( $method, get_class_methods( $makepot ) ) ) {
		$res = call_user_func(
			array( $makepot, $method ),         // Method
			realpath( $argv[2] ),               // Directory
			isset( $argv[3] )? $argv[3] : null, // Output
			isset( $argv[4] )? $argv[4] : null, // Slug
			isset( $argv[5] )? $argv[5] : null, // Excludes
			isset( $argv[6] )? $argv[6] : null  // Includes
		);

		if ( false === $res ) {
			fwrite( STDERR, "Couldn't generate POT file!\n" );
		}
	} else {
		$usage  = "Usage: php grunt-makepot.php PROJECT DIRECTORY [OUTPUT] [SLUG] [EXCLUDE] [INCLUDE]\n\n";
		$usage .= "Generate POT file from the files in DIRECTORY [OUTPUT]\n";
		$usage .= "Available projects: " . implode( ', ', $makepot->projects ) . "\n";
		fwrite( STDERR, $usage);
		exit( 1 );
	}
}
