# Alipay-Auto-Recharge
支付宝免接口自动充值
# 如何配置

#### 1.执行data.sql建立数据表

#### 2.打开Bill.php修改数据库信息

```
$mysqli = new mysqli("localhost", "test", "test", "data");		#数据库信息(服务器地址,用户名,密码,数据库名)
```

case后金额为套餐金额

#### 打开Command.php修改如下内容。

    $alipay = new Alipay([
        'cookie' => 'session.cookieNameId=ALIPAYJSESSIONID; ALIPAYJSESSIONID=xxxxxxx', //支付宝Cookie
        'notify' => '/Bill.php', //Bill.php所在地址，记得修改Bill.php内的MySQL信息
        'token' => 'please_input_your_token'    //安全密钥
        ]);

修改


    ALIPAYJSESSIONID=xxxxxxxxxxxxx
为你自己的Cookie

修改Bill.php所在地址

然后执行

    php Command.php
# License
GPL V3
