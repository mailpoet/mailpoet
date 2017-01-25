<?php
use MailPoet\Twig\Assets;

class AssetsTest extends MailPoetTest {
  function _before() {
    $this->assets_url = 'https://www.testing.com/wp-content/plugins/mailpoet/assets';
    $this->version = '1.2.3';
    $this->assetsExtension = new Assets(array(
      'assets_url' => $this->assets_url,
      'version' => $this->version
    ));
  }

  function testItGeneratesJavascriptTags() {
    expect($this->assetsExtension->generateJavascript('script1.js', 'script2.js'))->equals(
      '<script type="text/javascript" src="' . $this->assets_url . '/js/script1.js?mailpoet_version=' . $this->version . '"></script>'
      . "\n"
      . '<script type="text/javascript" src="' . $this->assets_url . '/js/script2.js?mailpoet_version=' . $this->version . '"></script>'
    );
  }

  function testItGeneratesStylesheetTags() {
    expect($this->assetsExtension->generateStylesheet('style1.css', 'style2.css'))->equals(
      '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style1.css?mailpoet_version=' . $this->version . '">'
      . "\n"
      . '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/style2.css?mailpoet_version=' . $this->version . '">'
    );
  }

  function testItGeneratesImageUrls() {
    expect($this->assetsExtension->generateImageUrl('image1.png'))->equals(
      $this->assets_url . '/img/image1.png?mailpoet_version=' . $this->version
    );
  }

  function testItAppendsVersionToUrl() {
    $without_file = 'http://url.com/';
    expect($this->assetsExtension->appendVersionToUrl($without_file))->equals(
      $without_file . '?mailpoet_version=' . $this->version
    );
    $with_file = 'http://url.com/file.php';
    expect($this->assetsExtension->appendVersionToUrl($with_file))->equals(
      $with_file . '?mailpoet_version=' . $this->version
    );
    $with_folder = 'http://url.com/folder/file.php';
    expect($this->assetsExtension->appendVersionToUrl($with_folder))->equals(
      $with_folder . '?mailpoet_version=' . $this->version
    );
    $with_query_string = 'http://url.com/folder/file.php?name=value';
    expect($this->assetsExtension->appendVersionToUrl($with_query_string))->equals(
      $with_query_string . '&mailpoet_version=' . $this->version
    );
  }
}
