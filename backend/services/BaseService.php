<?php
namespace backend\services;

use backend\traits\RequestTrait;
use backend\traits\DataTrait;
use backend\traits\ModelTrait;
use backend\traits\HierarchyTrait;

class BaseService {
    use RequestTrait, ModelTrait, DataTrait, HierarchyTrait;

    public $db = null;

    public function __construct() {
        $this->init();
        $this->db = \Yii::$app->db;
        if ($this->I('range_date')) {
            list($beg, $end) = explode(' - ', $this->I('range_date'));
            $this->I('start_date', $beg);
            $this->I('end_date', $end);
        }
    }

    public function init() {
        //dummy init
    }

    // public function jsonReturn(array $data) {
    //     return json_encode($data, JSON_UNESCAPED_UNICODE);
    // }

    protected function _searchParts() {
        $parts = ' round(sum(rd.origin_cost), 2) origin_cost,
                   round(sum(rd.cost), 2) cost,
                   sum(rd.reqs) reqs,
                   sum(rd.bids) bids,
                   sum(rd.shows) shows,
                   sum(rd.clicks) clicks,
                   ifnull(round(sum(rd.clicks)/sum(rd.shows)*100, 2), 0.00) clickRate,     
                   ifnull(round(sum(rd.cost)/sum(rd.shows)*1000, 2), 0.00) cpm,
                   ifnull(round(sum(rd.cost)/sum(rd.clicks), 2), 0.00) cpc ';
        return $parts;
    }

    public function getReportGraph($search='rd.logdate', $where='', $group='rd.logdate', $limit='', $dayType='') 
    {
        $parts = $this->_searchParts();
        $sql = "select $search
                       , $parts
                from {{%rpt_sumadbyday}} as rd ";
        
        if ($where) {
            $where = " where $where ";
            $sql .= preg_replace('/\s*where\s*where/i', ' where', $where);
        }
        if ($group) {
            $sql .= " group by $group ";
        }
        if ($limit) {
            $sql .= " $limit ";
        }

        $datas = $this->queryAll($sql, []);
        if ($dayType == 'month') {
            $datas = array_reverse($datas);
        }

        $x = $y0 = $y1 = $y2 = $y3 = $y4 = $y5 = [];
        foreach ($datas as $data) {
            $x[] = $data['logdate'];
            $y0[] = (float)$data['cost'];
            $y1[] = (int)$data['shows'];
            $y2[] = (int)$data['clicks'];
            $y3[] = (float)$data['clickRate'];
            $y4[] = (float)$data['cpm'];
            $y5[] = (float)$data['cpc'];
        }
        if (!$x) {
            $date = date('Ymd');
            if($dayType == 'week') { 
                $begin = date("w");
                $date = date('m/d', strtotime ( "-$begin day")).'~'.date('m/d');
            }
            $dayType == 'month' && $date = date("Y-m");
            $graph = ['x'=>[$date], 'y0' =>[0], 'y1' =>[0], 'y2'=>[0], 'y3'=>[0], 'y4'=>[0], 'y5'=>[0]];
            return $graph;
        }
        return ['x' => $x, 
                'y0' => $y0, 
                'y1' => $y1, 
                'y2' => $y2, 
                'y3' => $y3, 
                'y4' => $y4, 
                'y5' => $y5];
    }

    public function getAIds() {
        $advertiserId = $this->S('advertiser_id');
        $domainAdvertisers = $this->S('domainAdvertisers');
        if (!count($domainAdvertisers))     return [];
        $domainAdvertisers = array_column($domainAdvertisers, 'id');
        $advertiserId && $aIds = [$advertiserId];
        !$advertiserId && $aIds = $domainAdvertisers;
        $aIds = '( '.implode(',', $aIds). ')';
        return $aIds;
    }
}// end of base service
