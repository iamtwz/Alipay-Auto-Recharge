<?php  
#下面为垃圾代码示例
$data = file_get_contents('php://input'); 
$level = 'Z';
$dataarray = explode("&",$data);	#解析获取到的数据
if(empty($dataarray)) {
	echo 'Error';
}
else {
	$tradearray = explode("=",$dataarray[2]);	#分析金额
	$trade = $tradearray[1];
	if(is_numeric($trade)) {
		
		$dataarray[4] = str_replace(".00","",$dataarray[4]);	#抹零
		$amountarray = explode("=",$dataarray[4]);
		$amount = $amountarray[1];

		switch ($amount){	#case后为各个套餐金额
		case 5:
  			$level = "A";
  			echo "success";
  			break;
		case 10:
  			$level = "B";
  			echo "success";
  			break;
		case 15:
  			$level = "C";
  			echo "success";
  			break;
  		case 30:
  			$level = "D";
  			echo "success";
  			break;
		default:
  			echo "success";
  			exit();
		}
	}
	if ($level == 'Z') {
		exit();
	}
	else {
		$mysqli = new mysqli("localhost", "test", "test", "data");		#数据库信息(服务器地址,用户名,密码,数据库名)
		if (mysqli_connect_errno()) {
    		printf("Connect failed: %s\n", mysqli_connect_error());
    		exit();
		}
		$query = "INSERT INTO data VALUES ('$trade','$level','U')";		#写入数据，U代表未使用，C代表已使用
		$mysqli->query($query);
		$mysqli->close();		#释放链接
	}
}
#上面为垃圾代码示例
?>