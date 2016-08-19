<?php
/**
 * @package     Parable Http
 * @license     MIT
 * @author      Robin de Graaf <hello@devvoh.com>
 * @copyright   2015-2016, Robin de Graaf, devvoh webdevelopment
 */

namespace Parable\Http;

class Url {

    /** @var string */
    protected $baseurl;

    /**
     * Initialize the correct baseurl
     *
     * @return $this
     */
    public function buildBaseurl() {
        $url = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
        $this->baseurl = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseurl() {
        if (!$this->baseurl) {
            $this->buildBaseurl();
        }
        return $this->baseurl;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getUrl($url = '') {
        return $this->getBaseurl() . '/' . ltrim($url, '/');
    }

    /**
     * @return string
     */
    public function getCurrentUrl() {
        return isset($_GET['url']) ? $_GET['url'] : '/';
    }

    /**
     * Redirect to url, adding our own own baseUrl if it's probably a relative path
     *
     * @param $url
     */
    public function redirect($url) {
        if (
            strpos($url, 'http://') === false
            && strpos($url, 'https://') === false
        ) {
            $url = $this->getUrl($url);
        }
        header('location: ' . $url);
    }

}