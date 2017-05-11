<?php
namespace backend\services;

use backend\utils\Functions as F;

class HomeService extends BaseService {

    public function getAdervertiserAccount() {
        $advertiserId = $this->S('advertiser_id');
        $aIds = $this->getAIds();
        if (!$aIds) {
            return ['remind' => 0.00, 
                    'last' => 0.00, 
                    'balance' => 0.00];
        }
        $advertiser = '';
        $advertiserId && $advertiser = $this->M('Advertiser', $advertiserId);
        $balance = $this->getAdvertiserTodayBalance($aIds);
        $lastdayCost = $this->getAdvertiserCost($aIds, 1);
        $allCost = $this->getAdvertiserCost($aIds, 4);
        $advertiser && $remind = $advertiser['total_budget'];
        !$advertiser && $remind = (float)0.00;
        !$lastdayCost && $lastdayCost = (float)0.00;
        !$balance && $balance = (float)0.00;
        return ['remind' => $remind, 
                'last' => $lastdayCost, 
                'balance' => $balance];
    }

    public function getAdvertiserCost($advertiserId, $dateFlag) {
        list($beg, $end) = $this->getDateIdByFlag($dateFlag);
        $sql = "select round(sum(d.cost), 2) cost
                from {{%rpt_sumadbyday}} as d
                where d.logdate_id >= '$beg'
                      and d.logdate_id <= '$end'
                      and d.advertiser_id in $advertiserId";
        $data = $this->queryOne($sql);
        return $data['cost'];
    }

    public function getAdvertiserTodayBalance($advertiserId) {
        $balance = 0; 
        $serv = new SolutionService();
        $today = date('Y-m-d');
        $sql = "select id from {{%solution}} where advertiser_id in $advertiserId
                and status = 2 and end_date >= '$today'";       //只处理结束日期大于今天的策略，初始日期不计算。
        $solutions = $this->query($sql);
        foreach ($solutions as $solutionId) {
            $balance += $serv->getSolutionDailyBudget($solutionId);
        }
        return $balance;
    }

    public function getAdvertiserReportOnDateType($advertiserId, $dayType) {
        $aIds = $this->getAIds();
        if (!$aIds) return [];
        $where = " advertiser_id in $aIds ";
        $limit = 'order by logdate limit 14';
        switch ($dayType) {
            case 'day':
                $search =  'rd.logdate';
                $group = ' rd.logdate ';
                $where .= ' and rd.logdate  > (date_sub(current_date, INTERVAL 14 DAY)) and rd.logdate <= current_date ';
                break;
            case 'week':
                $search = 'concat(date_format(DATE_ADD(rd.logdate, INTERVAL(1-DAYOFWEEK(rd.logdate)) DAY), "%m/%d"),
                             "~", date_format(if(DATE_ADD(rd.logdate, INTERVAL(7-DAYOFWEEK(rd.logdate)) DAY) < current_date, 
                                DATE_ADD(rd.logdate, INTERVAL(7-DAYOFWEEK(logdate)) DAY), current_date), "%m/%d")) logdate ';
                $where .= ' and rd.logdate > (date_sub(current_date, INTERVAL 112 DAY)) ';
                $group = ' concat(date_format(DATE_ADD(rd.logdate, INTERVAL(1-DAYOFWEEK(rd.logdate)) DAY), "%m/%d"), 
                            "~", date_format(if(DATE_ADD(rd.logdate, INTERVAL(7-DAYOFWEEK(rd.logdate)) DAY) < current_date,
                             DATE_ADD(rd.logdate, INTERVAL(7-DAYOFWEEK(logdate)) DAY), current_date), "%m/%d")) ';
                break;
            case 'month':
                $search = 'date_format(rd.logdate, "%Y-%m") logdate';
                $group = ' date_format(rd.logdate, "%Y-%m")';
                $limit = ' order by logdate desc limit 12';
                break;
            default:
                $search = '';
                $group = '';
                break;
        }
        $data = $this->getReportGraph($search, $where, $group, $limit, $dayType);
        $data = $this->formData($data);         
        return $data;
    }
}//end
