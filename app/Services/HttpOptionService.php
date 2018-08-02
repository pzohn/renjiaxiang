<?php
namespace App\Services;

use Illuminate\Http\Request;

class HttpOptionService {
    private $options = [];
    private static $http_options = null;
    CONST TIME_OUT = 5;
    CONST CONNECT_TIME_OUT = 5;

    public function __construct() {
    }

    public static function getInstance() {
        if (null == self::$http_options) {
            self::$http_options = new self();
        }
        return self::$http_options;
    }
    
    public function setOptions(Request $req) {
        $headers = [
            'accept' => 'application/x-www-form-urlencoded',
            'referer' => $req->headers->get('referer'),
            'user-agent' => $req->headers->get('user-agent'),
            'x-forwarded-for' => $req->ip(),
        ];
        $this->options['headers'] = $headers;
        $this->options['timeout'] = self::TIME_OUT;
        $this->options['connect_timeout'] = self::CONNECT_TIME_OUT;
    }

    public function getOptions() {
        return $this->options;
    }
}