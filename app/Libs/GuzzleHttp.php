<?php
namespace App\Libs;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Services\HttpOptionService;

class GuzzleHttp {
    public static function multiPost($reqs_arr) {
        $client = new Client();
        $promises = [];
        foreach ($reqs_arr as $req) {
            $promises[$req['name']] = $client->postAsync($req['url'], self::options($req['post_data']));
        }

        $results = Promise\unwrap($promises);
        $resposes = [];
        foreach ($results as $name => $v) {
            $resposes[$name] = json_decode($v->getBody(), true);
        }
        return $resposes;
    }
    public static function multiGet($reqs_arr) {
        $client = new Client();
        $promises = [];
        foreach ($reqs_arr as $req) {
            $promises[$req['name']] = $client->getAsync($req['url']);
        }

        $results = Promise\unwrap($promises);
        $resposes = [];
        foreach ($results as $name => $v) {
            $resposes[$name] = json_decode($v->getBody(), true);
        }
        return $resposes;
    }

    public static function guzzlePost($url, $data_arr, $timeout = 5, $connect_time = 5) {
        $client   = new Client();
        $response = $client->post($url, self::options($data_arr));

        $rs_code = $response->getStatusCode();
        $res     = $response->getBody()->getContents();
        if ($rs_code != 200) {
            throw new \Exception("http status error($rs_code), $res");
        }

        return self::tryDecodeJson($res, true);
    }

    public static function guzzleGet($url, $data_arr, $timeout = 5, $connect_time = 5) {
        $client   = new Client();
        $url = $url . '?' .  http_build_query($data_arr);
        $response = $client->get($url, self::options());

        $rs_code = $response->getStatusCode();
        $res     = $response->getBody()->getContents();
        if ($rs_code != 200) {
            throw new \Exception("http status error($rs_code), $res");
        }

        return self::tryDecodeJson($res, true);
    }

    public static function postJson($url, $data_arr) {
        $http_client = new Client();
        $response = $http_client->post($url, self::options($data_arr, true));
        $rs_code = $response->getStatusCode();
        $res     = $response->getBody()->getContents();
        if ($rs_code != 200) {
            throw new \Exception("http status error($rs_code), $res");
        }

        return self::tryDecodeJson($res, true);
    }

    public static  function tryDecodeJson($str, $is_true = true) {
        if (is_array($str)) {
            return $str;
        }

        $json = json_decode($str, $is_true);
        if (is_array($json)) {
            return $json;
        }
        return $str;
    }

    public static function postXml($url, $data) {
        $http_client = new Client();
        $response = $http_client->post($url, self::options($data, false, true));
        $rs_code = $response->getStatusCode();
        $res     = $response->getBody()->getContents();
        if ($rs_code != 200) {
            throw new \Exception("http status error($rs_code), $res");
        }

        return self::tryDecodeJson($res, true);
    }

    public static function options($post_data = [], $is_json = false, $is_xml = false, $timeout = 5, $connect_time = 5) {
        $http_options = HttpOptionService::getInstance()->getOptions();
        $options = [
            'form_params' => $post_data,
            'connect_timeout' => $http_options['connect_timeout'],
            'timeout' => $http_options['timeout'],
            'headers' => $http_options['headers'],
        ];
        if ($is_json) {
            unset($options['form_params']);
            $options['json'] = $post_data;
            $options['headers']['Accept'] = 'application/json';
        }
        if ($is_xml) {
        	unset($options['form_params']);
            $options['body'] = $post_data;
            $options['headers']['Accept'] = 'application/xml';
        }
        if (empty($post_data)) {
            unset($options['form_params']);
        }
        return $options;
    }
}