<?php

namespace app\services;

class Executer
{
    protected $headers = array();
    protected $params = array();
    protected $options = array();

    protected function addHeader($row)
    {
        $this->headers[] = $row;
    }

    protected function addParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    protected function addArrayParams(array $params)
    {
        $this->params = $params;
    }

    protected function addStringParams(string $params)
    {
        $this->params = $params;
    }

    protected function addCurlOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
    * http://www.php.net/manual/ru/function.curl-exec.php
    */
    /**
    * Send a GET request using cURL
    * @param string $url to request
    * @param array $get values to send
    * @param array $options for cURL
    * @return string
    */
    protected function get($url)
    {
        $defaults = array(
            CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($this->params),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            // CURLOPT_DNS_USE_GLOBAL_CACHE => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => $this->headers
        );
        $ch = curl_init();
        curl_setopt_array($ch, ($this->options + $defaults));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // $code = curl_getinfo($ch);
        $result = curl_exec($ch);
        if (!$result) {
            trigger_error(curl_error($ch));
            // trigger_error(curl_errno($ch));
        }

        curl_close($ch);
        return $result;
    }

    /**
    * http://www.php.net/manual/ru/function.curl-exec.php
    */
    /**
    * Send a POST request using cURL
    * @param string $url to request
    * @param array|string $post values to send
    * @param array $options for cURL
    * @internal param array $get
    * @return string
    */
    protected function post($url, $isJson = false)
    {
        $params = $this->params ? $this->params : null;

        if ($params && $isJson) {
            $params = json_encode($params);
        }

        $defaults = array(
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FORBID_REUSE   => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_HTTPHEADER     => $this->headers
        );
        $ch = curl_init();
        curl_setopt_array($ch, ($this->options + $defaults));
        $result = curl_exec($ch);
        if(!$result){
            trigger_error(curl_error($ch));
            // print curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
}