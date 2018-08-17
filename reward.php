<?php
require_once 'RandReward.php';
class Reward
{
    public $redis;
    public $square;

    public function __construct($type)
    {
        $redis = new Redis();
        $redis->connect("127.0.0.1", 6379);
        $this->redis = $redis;
        $this->square = $type . ":rewards_square";
    }

    public function createReward($user_id, $data = array())
    {
        $redis = $this->redis;
        //$reward_id = $redis->incr($this->square);
        $reward_id = $data['reward_id'];
        // 红包信息(hash)
        $reward_key = $reward_id . ":info";
        foreach ($data as $key => $value) {
            $redis->hset($reward_key, $key, $value);
        }
        //个人红包列表 set
        $person_rewards = $user_id . ":reward";
        $redis->sadd($person_rewards, $reward_id);

        //随机小红包列表(list)
       /* $rand_reward_array = $this->splitReward($reward_id, $data['money'], $data['total_num']);*/
        $pipe = $redis->multi(Redis::PIPELINE);
        $rand_reward = new RandReward();
        $rand_reward_array = $rand_reward->splitReward($data['money'], $data['total_num'], 100);
        $rand_reward_list = $reward_id . ":splite_list";
        foreach ($rand_reward_array as $item) {
            /*$this->log('list_________', $item);*/
            $pipe->lpush($rand_reward_list, $item);
        }
        /*$redis->lRange($rand_reward_list, 0, -1);*/
        //红包添加红包广场
        $pipe->zadd($this->square, time(), $reward_id);
        $pipe->exec();
        $this->log("reward_id", $data);
    }

    public function getRewardSpliteList($reward_id, $data)
    {
        $redis = $this->redis;
        $rand_reward_array = $this->splitReward($reward_id, $data['money'], $data['total_num']);
        $rand_reward_list = $reward_id . ":splite_list";
        foreach ($rand_reward_array as $item) {
            $redis->lpush($rand_reward_list, $item);
        }
    }

    //红包广场信息
    public function getRewardSquare($start, $end)
    {
        $redis = $this->redis;
        $square = $this->square;
        $reward_ids = $redis->zrevrange($square, $start, $end);
        $square_data = array();
        $time = time();
        $this->log("<<<<<<<<<<<<红包广场信息", "单个红包信息");
        foreach ($reward_ids as $item) {
            $reward = $redis->hgetall($item . ":info");
            /*if($time - $red_pocket['create_time'] >172800){
                $square_data[] = $red_pocket;
            }*/
            $this->log('红包信息： ', $reward);
            $square_data[] = $reward;
        }
        $this->log("红包广场信息", ">>>>>>>>>>>>>");
        return $square_data;
    }

    //领取红包
    public function gainReward($user_id, $reward_id)
    {
        $redis = $this->redis;
        $reward_splite_list = $reward_id . ":splite_list";
        $reward_record = $reward_id . ":record";
        $reward_key = $reward_id . ":info";
        /*$red_pocket = $red_pocket_id;*/
        if ($redis->sismember($reward_record, $user_id)) {
            $this->log("------你已经领取过了-------", "");
            return;
        }
        while (true) {
            $redis->watch($reward_key);
            $rest_num = $redis->hget($reward_key, "rest_num");
            $this->log("------剩余个数-----", $rest_num);
            $lose_num = $redis->hget($reward_key, "lose_num");
            $this->log("------领取个数-----", $lose_num);
            $total_num = $redis->hget($reward_key, "total_num");
            $this->log("------总个数-----", $total_num);
            if ($rest_num - 1 >= 0) {
                $redis->multi();
                $redis->RPOP($reward_splite_list);
                $redis->hincrby($reward_key, "rest_num", -1);
                $redis->hincrby($reward_key, "lose_num", 1);
                $result = $redis->exec();
                if ($result) {
                    $redis->sadd($reward_record, $user_id);
                    $this->log("-----------恭喜领到红包---------用户id：$user_id   金额： ", $result[0]);
                    break;
                }
            } else {
                $this->log("-------红包已经领完了-------", "");
                return;
            }
        }
    }

    // 获取红包信息
    public function getRewardData($reward_id)
    {
        $redis = $this->redis;
        $reward_pocket = $redis->hgetall($reward_id);
        $reward_record = $reward_id . ":record";
        $user_ids = $redis->smembers($reward_record);
        $data['reward_info'] = $reward_pocket;
        $data['gain_info'] = array();
        $this->log('红包信息', $data);
        return $data;
    }

    public function splitReward($reward_id, $money, $total_num)
    {
        $money = (int)($money * 100);
        if ($total_num <= 0) {
            return;
        }
        if ($money < $total_num) {
            return;
        }

        $frozen_money = $total_num;
        $assign_money = $money - $frozen_money;
        $weights = [];
        $weight_sum = 0;
        for ($i = 0; $i < $total_num; $i++) {
            $weight = pow(mt_rand(0, 999), 1.5);
            $weights[] = $weight;
            $weight_sum += $weight;
        }
        // 按权数分配金额，向下取整以避免分配金额超过总金额
        $moneys = [];
        $money_sum = 0;
        foreach ($weights as $weight) {
            $money = (int)($weight / $weight_sum * $assign_money) + 1; // +1是将冻结的金额补上
            $moneys[] = $money;
            $money_sum += $money;
        }
        // 由于分配时向下取整实际分配数小于总金额，随机挑选一人，将未分配的金额分配到此人
        $lucky_num = mt_rand(0, $total_num - 1);  // 幸运者序号
        $rest_money = $money - $money_sum;   // 未分配金额
        $moneys[$lucky_num] += $rest_money;
        return $moneys;
    }

    public function log($msg, $data)
    {
        file_put_contents("output.log", $msg . "********" . json_encode($data) . PHP_EOL, FILE_APPEND);
    }
}
