<?php


namespace bonjour\obj;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\lottery_module\lottery_module_draw_no;
use bonjour\model\lottery_module\lottery_module_draw_no_params;
use bonjour\model\lottery_module\lottery_module_draw_no_template;

class obj_lottery_draw_no_manager
{
    public function info()
    {
        // 上一期 期号
        // 当前期号
        // 当前期号-封盘时间，开奖时间
        // 是否允许投注
    }

    // 是否允许投注
    public function allow_bet()
    {

    }

    /**
     * 期号生成器
     * 从模板中读取数据，生成期号
     *
     * @param \lottery\format\database\lottery_module\draw_no_params        $lottery_draw_no_params
     * @param string    $Ymd
     * @return \bonjour\format\res
     * @throws
     * */
    public function draw_no_generator($lottery_draw_no_params,string $Ymd)
    {
        $lottery_code = $lottery_draw_no_params->lottery_code;
        $week_day = date('w',strtotime($Ymd));

        // 检查是否开启模板的排期生成
        if($lottery_draw_no_params->enable_generate_draw_no != 1)
        {
            $msg = sprintf("彩种=%s 已经关闭生成排期!",$lottery_code);
            return new res(1,$msg);
        }

        // 检查最后更新时间
        // 如果已经更新过，不再更新
        if($lottery_draw_no_params->draw_no_cycle >= (int)$Ymd)
        {
            $msg = sprintf("彩种=%s Ymd=%s 已经生成过!",$lottery_code,$Ymd);
            return new res(1,$msg);
        }

        // 获取排期模板
        /* @var \lottery\format\database\lottery_module\draw_no_template[] $template_list */
        $template_list = array();
        $res = bonjour::$container->get(lottery_module_draw_no_template::class)->select_by_lottery_code_and_week('*',$lottery_code,$week_day);
        if($res->code) return $res;
        foreach ($res->qry as $row) $template_list[] = (object)$row;

        // 生成排期
        switch ($lottery_draw_no_params->draw_no_type)
        {
            case lottery_module_draw_no_params::$draw_no_type_date_num:
                $draw_no_basic = (int)date($lottery_draw_no_params->draw_no_format,strtotime($Ymd));

                try
                {
                    bonjour::$mysql->begin_transaction();

                    foreach ($template_list as $row)
                    {
                        $draw_no = $draw_no_basic + $row->num;

                        // 开奖时间
                        $time_of_draw_result = strtotime(sprintf("%s +%u day %s",$Ymd,$row->offset_day,$row->time_of_draw_result));
                        // 封盘时间
                        $time_of_stopping_selling = $time_of_draw_result - (int)$row->time_of_stopping_selling;
                        // 添加期号
                        $res = bonjour::$container->get(lottery_module_draw_no::class)->insert(
                            $lottery_code,
                            $draw_no,
                            $row->is_blocking,
                            $time_of_stopping_selling,
                            $time_of_draw_result
                        );
                        if($res->code) throw new \Exception('',1);
                    }

                    $res = bonjour::$container->get(lottery_module_draw_no_params::class)->update_draw_no_cycle_by_lottery_code($lottery_code,(int)$Ymd);
                    if($res->code) throw new \Exception('',1);

                    bonjour::$mysql->commit();
                }catch (\Exception $e)
                {
                    bonjour::$mysql->rollback();
                    if($e->getCode() == 1) return $res;
                    return new res(1,$e->getMessage());
                }
                break;
            case lottery_module_draw_no_params::$draw_no_type_increment:
                $draw_no_basic = (int)$lottery_draw_no_params->draw_no;

                try
                {
                    bonjour::$mysql->begin_transaction();

                    foreach ($template_list as $row)
                    {
                        $draw_no_basic++;
                        $draw_no = $draw_no_basic;

                        // 开奖时间
                        $time_of_draw_result = strtotime(sprintf("%s +%u day %s",$Ymd,$row->offset_day,$row->time_of_draw_result));
                        // 封盘时间
                        $time_of_stopping_selling = $time_of_draw_result - (int)$row->time_of_stopping_selling;
                        // 添加期号
                        $res = bonjour::$container->get(lottery_module_draw_no::class)->insert(
                            $lottery_code,
                            $draw_no,
                            $row->is_blocking,
                            $time_of_stopping_selling,
                            $time_of_draw_result
                        );
                        if($res->code) throw new \Exception('',1);
                    }

                    $res = bonjour::$container->get(lottery_module_draw_no_params::class)->update_draw_no_cycle_by_lottery_code($lottery_code,(int)$Ymd);
                    if($res->code) throw new \Exception('',1);

                    bonjour::$mysql->commit();
                }catch (\Exception $e)
                {
                    bonjour::$mysql->rollback();
                    if($e->getCode() == 1) return $res;
                    return new res(1,$e->getMessage());
                }
                break;
            default:
                return new res(1,'不支持的模板生成');
        }

        return new res();
    }

    /**
     * 期号更新器
     * 定时任务重复执行，可以把最新的一期，更新到 draw_no_params->draw_no
     *
     * @param \lottery\format\database\lottery_module\draw_no_params        $lottery_draw_no_params
     * @return \bonjour\format\res
     * @throws
     * */
    public function draw_no_updater($lottery_draw_no_params)
    {
        $lottery_code = $lottery_draw_no_params->lottery_code;
        $draw_no =      $lottery_draw_no_params->draw_no;

        $res = bonjour::$container->get(lottery_module_draw_no::class)->select_by_lottery_code_and_draw_no('*',$lottery_code,$draw_no,true);
        if($res->code)
        {
            $msg = sprintf("获取当前期号失败，彩种=%s ， 当前期号=%s",$lottery_code,$draw_no);
            return new res(1,$msg);
        }
        $draw_no_data = $res->qry->fetch_assoc();

        // 如果期号是阻塞，不进行变更
        if($draw_no_data['is_blocking']) return new res();

        // 如果当前期的开奖时间，还没有过，表示最新的期号，还是当前排期
        if(time() < (int)$draw_no_data['time_of_draw_result']) return new res();

        // 读取下一期的排期
        $res = bonjour::$container->get(lottery_module_draw_no::class)->select_next_draw_no_by_lottery_code_and_draw_no('`draw_no`',$lottery_code,$draw_no);
        if($res->code) return $res;
        if($res->qry->num_rows != 1)
        {
            $msg = sprintf("获取下一期失败，彩种=%s ，当前期号=%s",$lottery_code,$draw_no);
            return new res(1,$msg);
        }

        $new_draw_no_data = $res->qry->fetch_assoc();
        
        // 更新当前最新排期
        $res = bonjour::$container->get(lottery_module_draw_no_params::class)->update_draw_no_by_lottery_code($lottery_code,$draw_no,$new_draw_no_data['draw_no']);
        if($res->code) return $res;

        return new res();
    }
}