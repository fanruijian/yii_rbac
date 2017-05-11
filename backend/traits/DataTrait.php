<?php
namespace backend\traits;

trait DataTrait 
{
    //使用可以参考 UserModel 中的 $matrix  
    //$extra 为调用方传入的数据
    public function fieldsMap(array $map, array $data, $extra='')
    {
        $results = [];
        foreach ($data as $sigle) {
            $mix = [];
            foreach ($map as $k => $v) {
                //无应用规则/回调方法
                $value = '';
                if (!is_array($v)) {
                    isset($sigle[$v]) && $value = $sigle[$v];
                    $mix[$k] = $value;
                } else {
                    //若附带函数代码
                    list($args, $trans) = $v;
                    $args = explode(',', $args);
                    $trans = ' extract($args); '.$trans;
                    $func = create_function('$v,$args,$extra', $trans);
                    $mix[$k] = $func($sigle[trim($args[0])], $sigle, $extra); 
                }
            }
            $results[] = $mix;
        }
        return $results;
    }

    /**
     * 根据 $pk 将一维数组转化为按 $pk 索引的二维数组
     */
    public function arrayGroup(array $datas, $pk, $on=[])
    {
        $group = [];
        foreach ($datas as $k => $data) {
            if (empty($on)) {
                $group[$data[$pk]][] = $data;
                continue;
            }
            if (count($on) == 1) {
                $group[$data[$pk]][] = $data[$on[0]];
            } else {
                $sorted = [];
                foreach ($on as $key) {
                    $sorted[] = $data[$key];
                }
                $group[$data[$pk]][] = $sorted;
            }
        }
        return $group;
    }

    // 二维数组基于 $key 的第二维唯一化处理
    public function uniqueOn($data, $key)
    {
        $unique = [];
        $keeps = [];
        foreach ($data as $k => $d) {
            foreach ($d as $j => $i) {
                $pk = $i[$key];
                if (!isset($keeps[$k])) {
                    $keeps[$k][] = $pk;
                    $unique[$k][] = $i;
                } else if (in_array($pk, array_values($keeps[$k]))) {
                    continue;
                } else {
                    $keeps[$k][] = $pk;
                    $unique[$k][] = $i;
                }
            }
        }
        return $unique;
    }

    public function sortLevel(array $data, $pk, $map)
    {
        if(empty($data)) return [];
        $sort = [];
        list($parent, $child) = $map;
        foreach ($data as $d) {
            $sub = $d[$parent];
            if (!$sub || is_null($sub)) continue;
            $name = null;
            foreach ($data as $s) {
                if ($s[$parent]) continue;
                if ($s[$child] == $sub) {
                    $name = $s[$pk];
                }
            }
            $sort[$name][] = $d;
        }
        $sort = $this->uniqueOn($sort, $pk);
        return $sort;
    }

    public function sort2LevelCates($model, $indexBy, $adnetwork=1, $map=[])
    {
        $_model = 'backend\models\\'.$model;
        $_model = new $_model;
        $where = [];
        if (in_array('ad_network_id', array_keys($_model->attributes))) {
            $where = ['ad_network_id' => $adnetwork];
        }
        unset($_model);
        $cates = $this->getAll($model, $where);
        if (empty($map)) {
            $map = ['parent_category_id', 'category_id'];
        }
        return $this->sortLevel($cates, $indexBy, $map);
    }

    public function uniqueOnMultipleFields($datas, $indexBy='id')
    {
        $filter = [];
        foreach ($datas as $data) {
            $key = implode('$$', array_values($data));
            if (in_array($key, array_keys($filter))) {
                continue;
            }
            $filter[$key] = $data;
        }
        $datas = array_values($filter);
        return $datas;
    }

    /**
     * 获取数据的相关字段
     */
    public function getArrayField(array $data, array $keeps, $indexBy='id', $unique=true)
    {
        $matrix = array_combine($keeps, $keeps);
        $datas = $this->fieldsMap($matrix, $data);
        if ($unique) {
            $datas = $this->uniqueOnMultipleFields($datas, $keeps);
        }
        $indexData = [];
        foreach ($datas as $data) {
            $indexData[$data[$indexBy]] = $data; 
        }
        return $indexData;
    }

    public function getDateIdByFlag($flag=0)
    {
        $endTime = date('Ymd');
        $now = time();
        switch ($flag) {
            //today
            case 0:
                $begTime = date('Ymd', $now);
                break;
            //yestoday
            case 1: 
                $begTime = date('Ymd', strtotime('-1 day'));
                $endTime = date('Ymd', strtotime('-1 day'));
                break;
            //this week
            case 2:
                $span = '-'.date('w').' day';

                $begTime = date('Ymd', strtotime($span));
                break;
            // this month
            case 3:
                $begTime = date('Ym01');
                break;
            // all
            case 4:
                $begTime = '19700101';
                break;
            // this year
            case 5:
                $begTime = date('Y0101');
                break;
            // last month
            case 6:
                $time = $now;
                $begTime = date('Ym01',strtotime(date('Y',$time).'-'.(date('m',$time)-1).'-01'));
                $endTime = date('Ymd',strtotime("$begTime +1 month -1 day"));
                break;
            //last 7
            case 7:
                $begTime = date('Ymd', strtotime('-7 day'));
                break;
            //last 30
            case 8:
                $begTime = date('Ymd', strtotime('-30 day'));
                break;
            default:
                $begTime = '19700101';
                break;
        }
        return [(int)$begTime, (int)$endTime];
	}

    public function getTimeByFlag($flag=0)
    {
        $endTime = date('Y-m-d H:i:s');
        $now = time();
        switch ($flag) {
            //today
            case 0:
                $begTime = date('Y-m-d 00:00:00', $now);
                break;
            //yestoday
            case 1: 
                $begTime = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $endTime = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            //this week
            case 2:
                $span = '-'.date('w').' day';

                $begTime = date('Y-m-d 00:00:00', strtotime($span));
                break;
            // this month
            case 3:
                $begTime = date('Y-m-01 00:00:00');
                break;
            // all
            case 4:
                $begTime = '1970-01-01 00:00:00';
                break;
            // this year
            case 5:
                $begTime = date('Y-01-01 00:00:00');
                break;
            case 6:
                $time = $now;
                $begTime = date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)-1).'-01'));
                $endTime = date('Y-m-d',strtotime("$begTime +1 month -1 day"));
                break;
            default:
                $begTime = '1970-01-01 00:00:00';
                break;
        }
        return [$begTime, $endTime];
	}

    public function indexBy(array $datas, $field)
    {
        $indexData = [];
        foreach ($datas as $data) {
            $indexData[$data[$field]] = $data;
        }
        return $indexData;
    }

    public function json(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function arrayGroupOn(array $datas, $pk, $on)
    {
        !is_array($pk) && $pk = [$pk];
        $group = [];
        //$onData = $this->getArrayField($datas, $on);
        //print_r($onData); exit;
        foreach ($datas as $k => $data) {
            $jointPk = '';
            foreach ($pk as $field) {
                $jointPk[] = $data[$field];
            }
            $jointPk = implode($jointPk, '_');
            $group[$jointPk][] = $datas[$k];
        }
        return $group;
    }

    public function arraySumOn($datas, $sumFields)
    {
        $result = [];
        $allKeys = array_values($datas);
        $escapeKeys = $allKeys[0];
        $allKeys = array_keys($escapeKeys);
        foreach ($allKeys as $key) {
            $result[$key] = $escapeKeys[$key];
            if (in_array($key, $sumFields)) {
                $values = array_column($datas, $key);
                $result[$key] = array_sum($values);
            }
        }
        return $result;
    }

}//end
