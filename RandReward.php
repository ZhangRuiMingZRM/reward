<?php

class RandReward
{
    public $rewardMoney;        #红包金额、单位元
    public $rewardNum;                  #红包数量
    public $scatter;            #分散度值1-10000
    public $rewardArray = array();        #红包结果集

#执行红包生成算法
    public function splitReward($rewardMoney, $rewardNum, $scatter = 100)
    {
#传入红包金额和数量
        $this->rewardMoney = $rewardMoney;
        $this->rewardNum = $rewardNum;
        $this->scatter = $scatter;
        $this->realscatter = $this->scatter / 100;
        $avgRand = round(1 / $this->rewardNum, 4);
        $randArr = array();
        while (count($randArr) < $rewardNum) {
            $t = round(sqrt(mt_rand(1, 10000) / $this->realscatter));
            $randArr[] = $t;
        }
        $randAll = round(array_sum($randArr) / count($randArr), 4);
        $mixrand = round($randAll / $avgRand, 4);
        $rewardArr = array();
        foreach ($randArr as $key => $randVal) {
            $randVal = round($randVal / $mixrand, 4);
            $rewardArr[] = round($this->rewardMoney * $randVal, 2);
        }
        sort($rewardArr);
        $total_money = array_sum($rewardArr);
        var_dump("______________" . $total_money);
        var_dump("______________" . $rewardArr[$rewardNum - 1]);
        $rewardArr[$rewardNum - 1] = round($rewardMoney - ($total_money - $rewardArr[$rewardNum - 1]), 1);
        var_dump("______________" . $rewardArr[$rewardNum - 1]);
        var_dump("______________" . array_sum($rewardArr));
        shuffle($rewardArr);
        return $rewardArr;
    }
}

?>