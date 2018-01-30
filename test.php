<?php
require_once "reward.php";
function createUuid($num)
{
    $str = md5(uniqid(mt_rand(), true));
    $uuid = substr($str, 0, $num) . "-";
    return $uuid;
}

function num()
{
    return mt_rand(10, 1000);
}

function redPocketInfo()
{
    $url_pre = "http://www.";
    $url_end = ".com";
    $body = createUuid(8);
    $url = $url_pre . $body . $url_end;
    $array["user_id"] = createUuid(4);
    $array["nick_name"] = createUuid(5);
    $array["create_time"] = time();
    $array["url"] = $url;
    $array["reward_id"] = createUuid(6);
    $array["money"] = num();
    $array["total_num"] = (int)round($array['money'] * 1.5);
    $array["rest_num"] = mt_rand(0, $array['money']);
    $array["lose_num"] = $array['total_num'] - $array['rest_num'];
    return $array;
}

$reward = new Reward("voice");

//添加一条数据

$data = redPocketInfo();
$reward->createReward($data["user_id"], $data);

//$array = $reward->getRewardSquare(0, -1);


//添加数据
$user_id = createUuid(4);
$data = redPocketInfo();
echo "开始时间" . date("Y-m-d H:i:s");
for($i = 0; $i <= 1000000; $i++){
    $data = redPocketInfo();
    $reward->createReward($data["user_id"], $data);
    $reward->log("$i","--------");
}
echo "结束时间" . date("Y-m-d H:i:s");

//$reward->getRewardSquare(0, -1);
//抢红包
/*for ($i = 1; $i< 900; $i++ ) {
    $user_id = createUuid(4);
    $reward_id = "c50ea9-";
    $reward->gainReward($user_id, $reward_id);
    $reward->log('^^^^^^^^^^^^^^^^^^^^^^', $i);
}*/

/*for ($i = 0; $i < 1000000; $i++){
    $res = $reward->redis->get($data['user_id']);
    echo $i ."***" . $res . "\n";
}*/
?>
