<?php
namespace backend\services;

use backend\utils\Functions as F;
use backend\utils\Upload as Up;

/**
 * command service
 * 为 command 提供支持
 *
 * 刷新初始出价价格，仅当 solution 选择优化出价方式时有效
 */
class CommandService extends BaseService {
    /**
     * 补充 adspace 和 solution 的关联关系
     * 仅仅 insert 新的记录
     */
    public $videoMatrix = [
        'id' => 'id',
        'base_price' => ['base_price', '$v=explode(",", $v); $v=array_shift($v); return $v;'],
    ];

    public function freshOptPrice() {
        // get all adspace
        $today = date('Y-m-d');
        $sql = "select id, base_price from {{%adspace}}";
        $adspaces = $this->query($sql);
        $sql = "select id, base_price from {{%video_adspace}}";
        $vadspaces = $this->query($sql);
        $vadspaces = $this->fieldsMap($this->videoMatrix, $vadspaces);
        $sql = "select id, base_price from {{%mobile_adspace}}";
        $madspaces = $this->query($sql);
        $sql = "select id, base_price from {{%mobile_video_adspace}}";
        $mvadspaces = $this->query($sql);
        $adspaces = array_merge($adspaces, $vadspaces);
        $adspaces = array_merge($adspaces, $madspaces);
        $adspaces = array_merge($adspaces, $mvadspaces);
        $adspaces = $this->indexBy($adspaces, 'id');
        $sql = "select id, bid_type, optimize_bid_cpc, optimize_bid_cpm, 
                       optimize_ctr_cpm, optimize_ctr_target
                from {{%solution}} 
                where bid_type = 2 and end_date >= '$today' and status < 4";
                // where bid_type in (2,3) and end_date >= '$today' and status < 4";
        $solutions = $this->query($sql);
        if (!$solutions) return;
        $asmap = [];
        foreach ($solutions as $solution) {
            $solutionKeys[] = $solution['id'];
            foreach ($adspaces as $adsapce) {
                $as = ['adspace_id'     => $adsapce['id'],
                       'solution_id'    => $solution['id'],
                       'base_price'     => $adsapce['base_price'],
                       'status'         => 0,
                       'bid_type'       => $solution['bid_type'],
                ]; 
                if ($solution['bid_type'] == 2) {
                    $as['price']   = $solution['optimize_bid_cpm'];
                    $as['opt_cpc'] = $solution['optimize_bid_cpc'];
                    $as['opt_cpm'] = $solution['optimize_bid_cpm'];
                    $as['opt_ctr'] = 0;
                }
                // if ($solution['bid_type'] == 3) {
                //     $as['price']   = $solution['optimize_ctr_cpm'];
                //     $as['opt_cpc'] = 0;
                //     $as['opt_cpm'] = $solution['optimize_ctr_cpm'];
                //     $as['opt_ctr'] = $solution['optimize_ctr_target'];
                // }
                $asmap[] = $as;
            }
        }
        $solutionKeys = implode(',', $solutionKeys);
        $solutions = $this->indexBy($solutions, 'id');
        $this->saveMap($asmap, $solutionKeys, $adspaces, $solutions);
    }

    // public function getExistMaps($keys) {
    //     // get all exists map
    //     $sql = "select adspace_id, base_price from {{%opt_price}} group by adspace_id";
    //     $mapAdspaces = $this->query($sql);
    //     $sql = "select solution_id, bid_type, opt_cpc, opt_cpm, opt_ctr 
    //             from {{%opt_price}} group by solution_id";
    //     $mapSolutions = $this->query($sql);
    //     $sql = "select adspace_id, solution_id, base_price 
    //             from {{%opt_price}} where solution_id in ($keys)";
    //     $maps = $this->query($sql);
    //     $records = [];
    //     foreach ($maps as $map) {
    //         $records[$map['adspace_id'].'$$'.$map['solution_id']] = $map;
    //     }
    //     unset($maps);
    //     return [$records, $mapAdspaces, $mapSolutions];
    // }

    public function getExistSolutionAdspaceMaps($keys) {
        $sql = "select adspace_id, solution_id, base_price 
                from {{%opt_price}} where solution_id in ($keys)";
        $maps = $this->query($sql);
        $records = [];
        foreach ($maps as $map) {
            $records[$map['adspace_id'].'$$'.$map['solution_id']] = $map;
        }
        unset($maps);
        return $records;
    }

    public function addNewOptPriceRecord($asmap, $keys) {
        $maps = $this->getExistSolutionAdspaceMaps($keys);
        $newSql = [];
        foreach ($asmap as $map) {
            $key = $map['adspace_id'].'$$'.$map['solution_id'];
            if (!isset($maps[$key])) {
                $newSql[] = " ('${map['adspace_id']}',
                    '${map['solution_id']}',
                    '${map['bid_type']}',
                    '${map['opt_cpc']}',
                    '${map['opt_cpm']}',
                    '${map['opt_ctr']}',
                    '${map['base_price']}',
                    '${map['price']}')";
            } 
        }
        if (count($newSql)) {
            if (count($newSql) > 1000) {
                $newSql = array_chunk($newSql, 1000, true);
            } else {
                $newSql = [$newSql];
            }
            foreach ($newSql as $newS) {
                $sql = 'insert into {{%opt_price}} 
                    (adspace_id, solution_id, bid_type, opt_cpc, opt_cpm, opt_ctr, base_price, price) 
                    values '.implode(',', $newS).';';
                $this->executeSql($sql);
            }
        }
    }


    // 更新出价表
    public function saveMap($asmap, $keys, $adspaces, $solutions) {
        // get all exists map
        $this->addNewOptPriceRecord($asmap, $keys);
        $this->updateOptPriceRecord($asmap, $adspaces, $solutions);
    }

    public function updateOptPriceRecord($asmap, $adspaces, $solutions) {
        $this->updateOptPriceRecordOnAdspacePrice($asmap, $adspaces);
        $this->updateOptPriceRecordOnBidType($asmap, $solutions);
    }

    public function updateOptPriceRecordOnAdspacePrice($asmap, $adspaces) {
        $sql = "select adspace_id, base_price from {{%opt_price}} group by adspace_id";
        $mapAdspaces = $this->query($sql);
        $updateSql = [];
        foreach ($mapAdspaces as $mapAd) {
            if (!isset($adspaces[$mapAd['adspace_id']]['base_price'])) continue;
            if($mapAd['base_price'] != $adspaces[$mapAd['adspace_id']]['base_price']) {
                $updateSql[] = $adspaces[$mapAd['adspace_id']];
            }
        }
        if(count($updateSql)) {
            $updateAds = [];
            $sql = "update {{%opt_price}} set status = 1, base_price = CASE ";
            foreach ($updateSql as $upS) {
                $sql = $sql. "WHEN adspace_id = ${upS['id']} THEN ${upS['base_price']} ";
                $updateAds[] = $upS['id'];
            }
            $updateAds = implode(',', $updateAds);
            $sql = $sql."ELSE base_price END where adspace_id in (${updateAds})";
            $this->executeSql($sql);
        }
    }

    public function updateOptPriceRecordOnBidType($asmap, $solutions) {
        $sql = "select solution_id, bid_type, opt_cpc, opt_cpm, opt_ctr 
                from {{%opt_price}} group by solution_id";
        $mapSolutions = $this->query($sql);
        $updateSolutions = [];
        $sql = [];
        foreach ($mapSolutions as $mapSolu) {
            if(!isset($solutions[$mapSolu['solution_id']]))  continue;
            $solution = $solutions[$mapSolu['solution_id']];
            $bidType = $solution['bid_type'];
            $isSolutionBidChanged = false;
            if ($bidType != $mapSolu['bid_type']) {
                $isSolutionBidChanged = true;
            }
            if ($bidType == 2) {
                if($mapSolu['opt_cpc'] != $solution['optimize_bid_cpc'] 
                    || $mapSolu['opt_cpm'] != $solution['optimize_bid_cpm']) {
                        $isSolutionBidChanged = true;
                        $cpm = $solution['optimize_bid_cpm'];
                    }
            }
            // } else if ($bidType == 3) {
            //     if($mapSolu['opt_ctr'] != $solution['optimize_ctr_target'] 
            //         || $mapSolu['opt_cpm'] != $solution['optimize_ctr_cpm']) {
            //             $isSolutionBidChanged = true;
            //             $cpm = $solution['optimize_ctr_cpm'];
            //     }
            // }
            if ($isSolutionBidChanged) {
                    $cpc = $solution['optimize_bid_cpc'];
                    $ctr = $solution['optimize_ctr_target'];
                    if (!isset($cpm)) {
                        $bidType == 2 && $cpm = $solution['optimize_bid_cpm'];
                        $bidType == 3 && $cpm = $solution['optimize_ctr_cpm'];
                    }
                    $price = $cpm;
                    $solutionId = $solution['id'];
                    $sql[] = "update {{%opt_price}} set status = 1, 
                        opt_cpc = '$cpc', 
                        bid_type = '$bidType',
                        opt_cpm = '$cpm',
                        opt_ctr = '$ctr',
                        price   = '$cpm' where solution_id = $solutionId ";
            }
        }
        if ($sql) {
            if (count($sql) > 1000) {
                $sql = array_chunk($sql, 1000, true);
            } else {
                $sql = [$sql];
            }
            foreach ($sql as $updateSql) {
                $batchSql = implode(';', $updateSql);
                $this->executeSql($batchSql);
            }
        }
    }

    public function indexBy(array $datas, $field) {
        $indexData = [];
        foreach ($datas as $data) {
            $indexData[$data[$field]] = $data;
        }
        return $indexData;
    }
}//end
