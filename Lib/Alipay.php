<?php
/**
 * 支付宝免签约及时到账接口
 * @author Roope <admin@cxsir.com>
 * @version 1.0.3 [修复一些错误。]
 */
class Alipay {

    /**
     * 需要定时刷新的URL
     * @var string
     */
    private $url;

    /**
     * 通知地址
     * @var string
     */
    private $notify;

    /**
     * 支付宝Cookie，用document.cookie获取
     * @var string
     */
    private $cookie;

    /**
     * 通讯密钥
     * @var string
     */
    private $token;

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config) {

        require_once dirname(__FILE__) . '/SQLite.php';
        $this->db = new SQLite('db.db');
        $this->cookie = isset($config['cookie']) ? $config['cookie'] : exit('Cookie 错误。');
        $this->notify = isset($config['notify']) ? $config['notify'] : exit('通知地址错误。');
        $this->token = isset($config['token']) ? $config['token'] : exit('通讯密钥错误。');
        $days = time() - 86400;
        $this->url = 'https://consumeprod.alipay.com/record/advanced.htm?beginDate='.date('Y.m.d',$days).'&beginTime=00%3A00&endDate='.date('Y.m.d').'&endTime=24%3A00&dateRange=customDate&status=all&keyword=bizOutNo&keyValue=&dateType=createDate&minAmount=&maxAmount=&fundFlow=in&tradeType=ALL&categoryId=&_input_charset=utf-8';
    }


    /**
     * 请求支付宝
     * @return string 抓取回来的信息
     */
    public function requestURL() {

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, array(
            'Host: consumeprod.alipay.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0',
            'Accept: */*',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate',
            'DNT: 1',
            'Referer: https://authgtj.alipay.com/login/index.htm',
            'Connection: keep-alive'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        $response = curl_exec($ch);
        if(curl_errno($ch)) $response = curl_error($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 解析下返回来的信息
     * @return string 解析成功后的信息
     */
    public function parse() {

        require_once dirname(__FILE__) . '/simple_html_dom.php';

        $data = $this->requestURL();
        if(empty($data) || strlen($data) < 2000)  return null;  //如过抓取到的内容是空的或者小于2000字符说明cookie失效了。

        $html = new simple_html_dom();
        $html->load($data);
        $ymd = $html->find('.time-d');
        $his = $html->find('.time-h');
        $title = $html->find('.consume-title a');
        $trade = $html->find('td.tradeNo p');
        $name = $html->find('p.name');
        $amount = $html->find('td.amount span');

        if(!$trade) return 'no_order';
        $info = array();
        foreach ($ymd as $key => $value) {
            //只要订单数字部分
            preg_match('/\d+/',$trade[$key]->innertext,$tradeNo);
            $info[] = array(
                'time' => trim($ymd[$key]->innertext) . ' ' . trim($his[$key]->innertext),
                'title' => trim($title[$key]->innertext),
                'trade' => trim($tradeNo[0]),
                'name' => trim($name[$key]->innertext),
                'amount' => trim(str_replace('+', '', $amount[$key]->innertext))
            );
        }
        $html->clear();

        return $info;
    }

    /**
     * 通知接口
     * @param array $order 订单信息
     * @return string
     */
    public function notify(array $order) {

        $exists = $this->db->select('trade',['id','trade','has_notify'],['AND' => [['trade' => $order['trade']]]]);

        if(!$exists) {
            $this->db->insert('trade',[
                'time','title','trade','name','amount','has_notify'
            ],[
                $order['time'],$order['title'],$order['trade'],$order['name'],$order['amount'],0
            ]);

        } else {
            if($exists[0]['has_notify'] == 1) return '已经通知成功，无需再次通知。';
        }

        //MD5加密下当作签名传过去好校验是不是自己的
        $order['sig'] = strtoupper(md5($this->token));

        $ch = curl_init($this->notify);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($order));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        //print_r($response);
	if(curl_errno($ch)) return curl_error($ch);
        if($response == 'success') {
            $this->db->update('trade',['has_notify' => 1],['AND' => [['trade' => $order['trade']]]]);
            return 'success';
        } 
        else {
            if ($response == 'ErrorAmount') {
                return '金额错误';
            }
            else{
                return '服务器未返回正确的参数。';
            }

        }
    }

    /**
     * 运行程序
     * @return boolean
     */
    public function run() {

        $orderArr = $this->parse();
        if(empty($orderArr)) {
            echo "Cookie失效，请重新填写Cookie。\n";
            return false;
        } else if($orderArr == 'no_order') {
            echo "暂无订单。 \n";
            return false;
        }

        foreach($orderArr as $key => $order) {
            $notify = $this->notify($order);
	    if($notify == 'success') echo '订单: ' . $order['trade'] . " 通知成功。\n";
            else echo '订单: ' . $order['trade'] . " 通知失败。错误信息：$notify \n";
        }
    }
}
