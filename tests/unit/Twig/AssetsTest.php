<?php

use MailPoet\Config\Env;
use MailPoet\Twig\Assets;

class AssetsTest extends MailPoetTest {
  function __construct() {
    $this->assets_url = 'https://www.testing.com/wp-content/plugins/mailpoet/assets';
    $this->assets_path = Env::$temp_path;
    $this->assets_path_js = $this->assets_path . '/js';
    $this->assets_path_css = $this->assets_path . '/css';
    $this->assets_manifest_js = $this->assets_path_js . '/manifest.json';
    $this->assets_manifest_css = $this->assets_path_css . '/manifest.json';
    $this->version = '1.2.3';
    $this->assets_extension = new Assets(
      array(
        'assets_url' => $this->assets_url,
        'assets_path' => $this->assets_path,
        'version' => $this->version
      )
    );
    $this->_after(); // delete folders if any were present before the test suite was initialized
  }

  function _before() {
    $this->createDir($this->assets_path_js);
    $this->createDir($this->assets_path_css);
  }

  function testItGeneratesJavascriptTagsForAssetsUsinManifestFile() {
    $manifest = file_put_contents(
      $this->assets_manifest_js,
      json_encode(
        array(
          'script1.js' => 'script1.hash.js',
          'script2.js' => 'script2.hash.js'
        )
      )
    );
    expect($this->assets_extension->generateJavascript('script1.js', 'script2.js'))->equals(
      '<script type="text/javascript" src="' . $this->assets_url . '/js/script1.hash.js"></script>'
      . "\n"
      . '<script type="text/javascript" src="' . $this->assets_url . '/js/script2.hash.js"></script>'
    );
  }

  function testItGeneratesJavascriptTagsForAssetsWhenManifestFileDoesNotExist() {
    expect($this->assets_extension->generateJavascript('script1.js', 'script2.js'))->equals(
      '<script type="text/javascript" src="' . $this->assets_url . '/js/script1.js"></script>'
      . "\n"
      . '<script type="text/javascript" src="' . $this->assets_url . '/js/script2.js"></script>'
    );
  }

  function testItGeneratesStylesheetTagsForAssetsUsingManifestFile() {
    $manifest = file_put_contents(
      $this->assets_manifest_css,
      json_encode(
        array(
          'style1.css' => 'style1.hash.css',
          'style2.css' => 'style2.hash.css'
        )
      )
    );
    expect($this->assets_extension->generateStylesheet('style1.css', 'style2.css'))->equals(
      '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style1.hash.css" />'
      . "\n"
      . '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style2.hash.css" />'
    );
  }

  function testItGeneratesStylesheetTagsWhenManifestFileDoesNotExist() {
    expect($this->assets_extension->generateStylesheet('style1.css', 'style2.css'))->equals(
      '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style1.css" />'
      . "\n"
      . '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style2.css" />'
    );
  }

  function testItGeneratesImageUrls() {
    expect($this->assets_extension->generateImageUrl('image1.png'))->equals(
      $this->assets_url . '/img/image1.png?mailpoet_version=' . $this->version
    );
  }

  function testItAppendsVersionToUrl() {
    $without_file = 'http://url.com/';
    expect($this->assets_extension->appendVersionToUrl($without_file))->equals(
      $without_file . '?mailpoet_version=' . $this->version
    );
    $with_file = 'http://url.com/file.php';
    expect($this->assets_extension->appendVersionToUrl($with_file))->equals(
      $with_file . '?mailpoet_version=' . $this->version
    );
    $with_folder = 'http://url.com/folder/file.php';
    expect($this->assets_extension->appendVersionToUrl($with_folder))->equals(
      $with_folder . '?mailpoet_version=' . $this->version
    );
    $with_query_string = 'http://url.com/folder/file.php?name=value';
    expect($this->assets_extension->appendVersionToUrl($with_query_string))->equals(
      $with_query_string . '&mailpoet_version=' . $this->version
    );
  }

  function _after() {
    $this->removeDir($this->assets_path_js);
    $this->removeDir($this->assets_path_css);
  }

  private function createDir($dir) {
    if(is_dir($dir)) return;
    mkdir($dir);
  }

  private function removeDir($dir) {
    if(!is_dir($dir)) return;
    array_map('unlink', glob($dir . '/*.*'));
    rmdir($dir);
  }
}