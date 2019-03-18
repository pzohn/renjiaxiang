<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Qcloud\Sms\SmsSingleSender;

class SmsTest extends Command
{
    public static $appid = 1400184176;
    public static $appkey = 'c5f98a9fd6a8828dea964516fc98e574';
    public static $phones = ['18567351516'];
    public static $templateId = 295943;
    public static $smsSign = 'hubin';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qcloud:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '腾讯下发短信';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	try {
	    $sender = new SmsSingleSender(self::$appid, self::$appkey);
	    $params = ["1234"];
	    $result = $sender->sendWithParam("86", self::$phones[0], self::$templateId, $params, self::$smsSign, "", "");
	    $res = json_decode($result, true);
	    var_dump($res);
	} catch (\Exception $e) {
	    var_dump($e);
	}	
    }
}
