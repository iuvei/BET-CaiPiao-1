<?php
/**
 * 比赛基本信息
 * 手动校对数据
 */

require_once 'global.func.php';
require_once 'conn.php';

//获取校对记录
fwrite(STDOUT, 'Please enter game ids. (e.g 1381621|1381621,1381622)' . "\n");
$gameids = trim(fgets(STDIN));
while (!preg_match('/^\d[\d,]*\d$/', $gameids)) {
    fwrite(STDOUT, 'Please enter game ids again.' . "\n");
    $gameids = trim(fgets(STDIN));
}
$gameids = explode(',', $gameids);

//从7m接口获取数据并写入本地数据库
$sql = "REPLACE INTO `ft_game` VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
if ($stmt = $mysqli->prepare($sql))
{
    foreach ($gameids as $gameid)
    {
        //通过接口获取数据
        $type = 'type=getgameinfo&p1=' . $gameid;
        $return = http_get(proxy_url() . $type);

        //确认返回数据格式正确
        if (is_array($return) && !empty($return) && !isset($return['error']))
        {
            $date = floor($return['Date'] / 1000);
            $note = $stats = $none =  '';
            $homephoto = isset($return['HomeTeam']['Photo']) ? $return['HomeTeam']['Photo'] : $none;
            $awayphoto = isset($return['AwayTeam']['Photo']) ? $return['AwayTeam']['Photo'] : $none;
            $homescore = isset($return['ScoreAll']) ? $return['ScoreAll'][0] : $return['Score'][0];
            $awayscore = isset($return['ScoreAll']) ? $return['ScoreAll'][1] : $return['Score'][1];
            $stadium = isset($return['Stadium']) ? $return['Stadium'] : $none;
            $localtime = isset($return['Localtime']) ? $return['Localtime'] : $none;
            $referee = isset($return['Referee']) ? $return['Referee'] : $none;
            $capacity = isset($return['Capacity ']) ? $return['Capacity '] : $none;
            $spectator = isset($return['Spectator']) ? $return['Spectator'] : $none;
            $handicap = !empty($return['handicap']) ? floatval($return['handicap']) : 0.00;
            $stmt->bind_param(
                'iisssissisissisiiiiiiiiisdsssssssss',
                $gameid,                                                                    //比赛编号
                $return['Competition']['Id'],                                               //联赛编号
                $return['Competition']['Name'],                                             //联赛名称
                $return['Competition']['ShortName'],                                        //联赛简称
                $return['Competition']['Color'],                                            //联赛颜色
                $return['HomeTeam']['Id'],                                                  //主队编号
                $return['HomeTeam']['Name'],                                                //主队名称
                $return['HomeTeam']['ShortName'],                                           //主队简称
                $return['HomeTeam']['Rank'],                                                //主队排名
                $homephoto,    //主队图标
                $return['AwayTeam']['Id'],                                                  //客队编号
                $return['AwayTeam']['Name'],                                                //客队名称
                $return['AwayTeam']['ShortName'],                                           //客队简称
                $return['AwayTeam']['Rank'],                                                //客队排名
                $awayphoto,    //客队图标
                $date,                                                                      //开赛时间
                $return['N'],                                                               //是否中立
                $return['Status'],                                                          //比赛状态值
                $homescore,  //主队得分
                $awayscore,  //客队得分
                $return['RedCard'][0],                                                      //主队红牌
                $return['RedCard'][1],                                                      //客队红牌
                $return['YellowCard'][0],                                                   //主队红牌
                $return['YellowCard'][1],                                                   //客队红牌
                $return['Half'],                                                            //半场比分
                $handicap,                                                        //让球
                $return['Channel'],                                                         //直播频道
                $return['Weather'],                                                         //天气
                $stadium,                     //比赛场地
                $localtime,                 //当地时间
                $referee,                     //裁判
                $capacity,                 //球馆容量
                $spectator,                 //现场观众
                $note,                                                                      //备注信息
                $stats                                                                      //比赛统计数据(json数据)
            );

            $stmt->execute();
            cron_log('IP:' . IP . ' ' . $type . ' 请求接口数据成功');
        } elseif (isset($return['error'])) {
            cron_log('IP:' . IP . ' ' . $type . ' ' . $return['error'], 1);
        } elseif (empty($return)) {
            cron_log('IP:' . IP . ' ' . $type . ' 空值' . json_encode($return), 1);
        } else {
            cron_log('IP:' . IP . ' ' . $type . ' 网络错误', 1);
        }

        unset($return);
    }

    $stmt->close();
} else {
    cron_log('初始化语句对象失败。', 2);
}

// 关闭mysql连接
$mysqli->close();
