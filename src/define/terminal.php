<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/2/21
 * Time: 21:01
 */

// 终端设备类型


define("br_terminal_type_mobile",    0);         // 手机
define("br_terminal_type_pc",        1);         // PC
define("br_terminal_type_tablet",    2);         // 平板电脑

define('br_terminal_type_web',          'web');
define('br_terminal_type_android',      'android');
define('br_terminal_type_ios',          'ios');

define('br_terminal_type',          array(
    ['title'=>'网页','value'=>br_terminal_type_web],
    ['title'=>'苹果','value'=>br_terminal_type_ios],
    ['title'=>'安卓','value'=>br_terminal_type_android],
));

define('br_lobby_game_type_hot_game',       'hot_game');
define('br_lobby_game_type_poker_chess',    'poker_chess');
define('br_lobby_game_type_fisher_hunter',  'fisher_hunter');
define('br_lobby_game_type_real_human',     'real_human');
define('br_lobby_game_type_video_game',     'video_game');
define('br_lobby_game_type',        array(
    ['title'=>'热门游戏','value'=>br_lobby_game_type_hot_game],
    ['title'=>'棋牌游戏','value'=>br_lobby_game_type_poker_chess],
    ['title'=>'捕鱼游戏','value'=>br_lobby_game_type_fisher_hunter],
    ['title'=>'真人视讯','value'=>br_lobby_game_type_real_human],
    ['title'=>'电子游戏','value'=>br_lobby_game_type_video_game]
));

define('br_ti_type',          array(
    ['title'=>'微信','value'=>1],
    ['title'=>'支付宝','value'=>2],
    ['title'=>'QQ','value'=>3],
    ['title'=>'银联','value'=>4],
    ['title'=>'银行转账','value'=>5],
    ['title'=>'VIP转账','value'=>6],
    ['title'=>'微信人工','value'=>7],
    ['title'=>'支付宝人工','value'=>8],
));

define('br_game_type_poker_chess',      0);;        // 棋牌游戏
define("br_game_type_video_game",       1);         // 电子游戏
define("br_game_type_real_human",       2);         // 真人视讯
define('br_game_type_fisher_hunter',    3);         // 捕鱼类

