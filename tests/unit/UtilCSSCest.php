<?php

class UtilCSSCest {
    public function _before() {
        $this->css = new \MailPoet\Util\CSS();
    }

    // tests
    public function it_can_be_instantiated() {
        expect_that($this->css instanceof \MailPoet\Util\CSS);
    }

    public function it_can_parse_css() {
        $styles_array = array();
        try {
            $url = dirname(__DIR__).'/../assets/css/admin.css';
            $css = $this->css->getCSS($url);
            $styles_array = $this->css->parseCSS($css);
        } catch(Exception $e) {}
        expect($styles_array)->notEmpty();
    }
}
