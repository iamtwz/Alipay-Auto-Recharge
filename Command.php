<?php
#设置下时期，照顾强迫症
date_default_timezone_set('Asia/Shanghai');
date_default_timezone_set('Asia/Chongqing');
date_default_timezone_set('PRC');
ini_set('date.timezone','Etc/GMT-8');
ini_set('date.timezone','PRC');
ini_set('date.timezone','Asia/Shanghai');
ini_set('date.timezone','Asia/Chongqing');

require_once dirname(__FILE__) . '/Lib/Alipay.php';
$alipay = new Alipay([
    'cookie' => 'session.cookieNameId=ALIPAYJSESSIONID; ALIPAYJSESSIONID=xxxxxxx', //支付宝Cookie
    'notify' => '/Bill.php', //Bill.php所在地址，记得修改Bill.php内的MySQL信息
    'token' => 'please_input_your_token'    //安全密钥
    ]);

while (true) {
    echo date('Y-m-d H:i:s') . "\n";
    $alipay->run();
    usleep(5000000);
}


