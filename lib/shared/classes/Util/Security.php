<?php
namespace Util;

/**
 * Contains static utility functions for ensuring the security of the website and the user inputed data.
 */
class Security {

    /**
     * Escapes all HTML characters, replacing them with their encodings.
     * 
     * This helps prevent XSS atacks when user data is input.
     *
     * @param string $string the HTML content to escape
     * @param string $encoding the encoding to use. Defaults to 'UTF-8'.
     * @return string the escaped content
     */
    public static function HtmlEntitiesEncode($string, $encoding = 'UTF-8') {
        return \htmlentities($string, ENT_QUOTES | ENT_HTML5, $encoding);
    }

    /**
     * Encodes a URL string.
     *
     * @param string $string the link to encode
     * @param bool $validate indicates whether to also validate the URL with default protocol schems before encoding 
     * it. Defaults to true.
     * @return string the encoded URL link
     */
    public static function UrlEncode($string, $validate = true) {
        if($validate) {
            return \urlencode(self::ValidateUrl($string));
        }
        return \urlencode($string);
    }

    /**
     * Ensures the provided URL is valid
     *
     * @param string $url the URL to validate
     * @param string[] $allowedSchemes the valid URL protocols. Defaults to ['http', 'https'].
     * @return void
     */
    public static function ValidateUrl($url, $allowedSchemes = array('http', 'https')) {
        if($url == null) {
            return null;
        }
        $parsed = \parse_url($url);
        if (!\is_array($parsed)) {
            return '#';
        }
        if (isset($parsed['scheme']) && !\in_array($parsed['scheme'], $allowedSchemes, true)) {
            return '#';
        }
        return $url;
    }

    /**
     * Validates email input
     *
     * @param string $email the email to validate
     * @return string|bool the email if it is formatted correctly, false otherwise.
     */
    public static function ValidateEmail($email) {
        return \filter_var($email, \FILTER_VALIDATE_EMAIL);
    }
}
