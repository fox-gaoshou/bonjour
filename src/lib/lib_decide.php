<?php


namespace bonjour\lib;


class lib_decide
{
    /**
     * 必须要正序排列
     * less_than的值，由小到大
     * 返回数组的索引值，如果数值，超过所有的匹配数据，则返回最大的索引值，也等于匹配了最后的数据
     *
     * @param string            $key
     * 需要对比的key
     * @param float             $val
     * 需要对比的val
     * @param array             $rows
     * 用于对比的数据
     *
     * @return int
     *
     * */
    public function less_than(string $key,float $val,array $rows)
    {
        // 默认获取最大的值
        $ret_index = count($rows)-1;

        foreach ($rows as $index => $row)
        {
            if($val < $row[$key])
            {
                $ret_index = $index;
                break;
            }
        }

        return $ret_index;
    }
}