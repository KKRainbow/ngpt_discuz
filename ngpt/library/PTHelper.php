<?php
/**
 * Created by PhpStorm.
 * User: MIC
 * Date: 2015/4/16
 * Time: 0:13
 */

require_once DISCUZ_ROOT . "source/plugin/ngpt/config.inc.php";

class PTHelper
{
    const IPV4_IN_BUAA = 0;
    const IPV4_IN_FRIENDLY_AREAS = 1;
    const IPV4_UNACCEPTABLE = 99;
    const IPV6_IN_BUAA_NATIVE = 100;
    const IPV6_IN_BUAA_TEREDO = 101;
    const IPV6_IN_BUAA_ISATAP = 102;
    const IPV6_UNACCEPTABLE = 199;
    const IP_PARSE_ERROR = 999;

    /**
     * @param string $ipstr
     * @return int
     */
    public static function GetIPType($ipstr)
    {
        $ipv4_regex = '/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';
        $ipv6_regex = '/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/';
        if (preg_match($ipv4_regex, $ipstr)) {
            $iparr = explode('.', $ipstr);
            $ipval = self::Ipv4ToLong($iparr);
            // 原来学长在这里犯了一个小小的错误，应该是可以等于的，而不是严格小于/大于
            if (self::Ipv4Inside([172, 16, 0, 0], $ipval, [172, 31, 255, 255])) {
                // 沙河校区和大运村
                return self::IPV4_IN_BUAA;
            } elseif (self::Ipv4Inside([192, 168, 0, 0], $ipval, [192, 168, 255, 255])) {
                // 本部宿舍区
                return self::IPV4_IN_BUAA;
            } elseif (self::Ipv4Inside([115, 25, 128, 0], $ipval, [115, 25, 191, 255])) {
                // 沙河校区
                return self::IPV4_IN_BUAA;
            } elseif (self::Ipv4Inside([219, 224, 128, 0], $ipval, [219, 224, 192, 255])) {
                // 新主楼
                return self::IPV4_IN_BUAA;
            } elseif (self::Ipv4Inside([211, 71, 0, 0], $ipval, [211, 71, 15, 255])) {
                // 其他地区
                return self::IPV4_IN_FRIENDLY_AREAS;
            } elseif (self::Ipv4Inside([202, 112, 128, 0], $ipval, [202, 112, 143, 255])) {
                // 其他地区
                return self::IPV4_IN_FRIENDLY_AREAS;
            } elseif (self::Ipv4Inside([219, 239, 227, 0], $ipval, [219, 239, 227, 255])) {
                // 其他地区
                return self::IPV4_IN_FRIENDLY_AREAS;
            } elseif (self::Ipv4Inside([58, 194, 224, 0], $ipval, [58, 194, 31, 255])) {
                // 其他地区
                return self::IPV4_IN_FRIENDLY_AREAS;
            } elseif (self::Ipv4Inside([58, 195, 8, 0], $ipval, [58, 195, 16, 255])) {
                // 原文 58.195.8.1/21 mask 255.255.248.0    58.195.16.1/24 mask 255.255.255.0
                // 其他地区
                return self::IPV4_IN_FRIENDLY_AREAS;
            } elseif (self::Ipv4Inside([10, 0, 0, 0], $ipval, [10, 255, 255, 255])) {
                // 其他地区（内网？）
                return self::IPV4_IN_FRIENDLY_AREAS;
            }
            return self::IPV4_UNACCEPTABLE;
        } elseif (preg_match($ipv6_regex, $ipstr)) {
            if (stripos($ipstr, '2001:0') == 0) {
                return self::IPV6_IN_BUAA_TEREDO;
            } elseif (stripos($ipstr, '2001:da8:203') == 0) {
                if (stripos($ipstr, '2001:da8:203:888:') == 0 or stripos($ipstr, '2001:da8:203:666:') == 0) {
                    return self::IPV6_IN_BUAA_ISATAP;
                } elseif (stripos($ipstr, ':0:5efe:') > 0) {
                    return self::IPV6_IN_BUAA_ISATAP;
                } else {
                    return self::IPV6_IN_BUAA_NATIVE;
                }
            } elseif (stripos($ipstr, '2001:da8:ae') == 0) {
                return self::IPV6_IN_BUAA_NATIVE;
            }
            return self::IPV6_UNACCEPTABLE;
        } else {
            return self::IP_PARSE_ERROR;
        }
    }

    /**
     * 将 IPv4 数组（顺序：1.2.3.4 = [1, 2, 3, 4]）转换为整数（输出为浮点数）
     * @param array $ipv4 [addr0, addr1, addr2, addr3]
     * @return float
     */
    public static function Ipv4ToLong($ipv4)
    {
        // 不好用移位（考虑溢出），只好借助于浮点数
        $ret = 0.0;
        $ret += 16777216 * $ipv4[0];
        $ret += 65536 * $ipv4[1];
        $ret += 256 * $ipv4[2];
        $ret += $ipv4[3];
        return ret;
    }

    /**
     * @param array $iparr1
     * @param float $ipval
     * @param array $iparr2
     * @return bool
     */
    private static function Ipv4Inside($iparr1, $ipval, $iparr2)
    {
        return (self::Ipv4ToLong($iparr1) <= $ipval and $ipval <= self::Ipv4ToLong($iparr2));
    }

    public static function GetIPString()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $ip = getenv("HTTP_CLIENT_IP");
            } else {
                $ip = getenv("REMOTE_ADDR");
            }
        }
        return $ip;
    }

    /**
     * @param mixed $var
     * @return bool
     */
    public static function IsBool($var)
    {
        if (is_string($var)) {
            if (empty($var) or strtoupper($var) === 'TRUE' or strtoupper($var) === 'FALSE') {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public static function GetBoolValue($var)
    {
        if (is_string($var)) {
            if (strtoupper($var) === 'TRUE') {
                return true;
            } else if (strtoupper($var) === 'FALSE') {
                return false;
            }
        }
        return $var and true;
    }

    public static function getResourceForumList()
    {
        global $_G;
        $field_table = DB::table("forum_forumfield");
        $forum_table = DB::table("forum_forum");
        $sql = <<<SQL
    SELECT fid FROM {$field_table} JOIN {$forum_table} USING (fid)
WHERE threadplugin LIKE '%ngpt%';
SQL;
        $resource = DB::fetch_all($sql);
        $resfid = array();
        foreach ($resource as $r) {
            $resfid[] = $r['fid'];
        }
        return $resfid;
    }

    public static function isForumHasResource($fid)
    {
        $resfid = static::getResourceForumList();
        return in_array($fid, $resfid);
    }

    public static function getPassKey()
    {
        global $_G;
        if (empty($_G['passkey'])) {
            //获取用户passkey
            $tbn = DB::table("ngpt_user");
            $sql = <<<SQL
SELECT passkey FROM {$tbn} WHERE uid={$_G['uid']};
SQL;
            $arr = DB::fetch_first($sql);
            if (!$arr) {
                return null;
            }
            $_G['passkey'] = $arr['passkey'];
        }
        return $_G['passkey'];
    }

    public static function getApiUrl($interface, $params = null)
    {
        global $_G;
        $api = $_G['ngpt']['api'];
        $api .= $interface;

        $passkey = static::getPassKey();
        if (!$passkey) {
            return null;
        }

        $api .= "&passkey={$passkey}";
        if (!empty($params)) {
            $api .= '&' . http_build_query($params);
        }
        return $api;
    }

    public static function getApiCurl($interface, $data = [], $post = false)
    {
        global $_G;
        $api = static::getApiUrl($interface);

        if (empty($api)) {
            throw new Exception("无法获取api地址，是否登录？");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
           "Accept: application/json; q=1.0, */*; q=0.1",
        ]);
        if ($post) {
            curl_setopt($ch, CURLOPT_URL, $api);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            $api = $api . '&' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $api);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    /**
     * @param float $minutes
     * @return string
     */
    public static function getReadableTimeFromMinutes($minutes)
    {
        $rarr = array();

        if ($minutes >= 1) {
            if ($minutes < 60) {
                $v = intval($minutes);
                $v > 0 and ($rarr[] = $v . '分');
            } else {
                $v = intval($minutes % 60);
                $v > 0 and ($rarr[] = $v . '分');
            }
            $minutes = $minutes / 60;
        } else {
            $rarr[] = '0分';
        }

        if ($minutes >= 1) {
            if ($minutes < 24) {
                $v = intval($minutes);
                $v > 0 and ($rarr[] = $v . '时');
            } else {
                $v = intval($minutes % 24);
                $v > 0 and ($rarr[] = $v . '时');
            }
            $minutes = $minutes / 24;
        }

        if ($minutes >= 1) {
            if ($minutes < 365) {
                $v = intval($minutes);
                $v > 0 and ($rarr[] = $v . '天');
            } else {
                $v = intval($minutes % 365);
                $v > 0 and ($rarr[] = $v . '天');
            }
            $minutes = $minutes / 365;
        }

        if ($minutes >= 1) {
            $v = intval($minutes);
            // 条件是恒成立的
            $v > 0 and ($rarr[] = intval($minutes) . '年');
        }

        $c = count($rarr);
        if ($c > 1) {
            $ret = $rarr[$c - 1] . $rarr[$c - 2];
        } else {
            // 总会至少有1项
            $ret = $rarr[0];
        }

        return $ret;
    }

    /**
     * @param string $size Used to perform GMP operations.
     * @return string
     */
    public static function getReadableFileSize($size)
    {
        /**
         * @var string $v
         * @var string $u
         */
        // 注意，大于 2 GB 级别的都只能由 64 位机器处理
        if ($size < 1024) {
            $v = strval($size);
            $u = ' B';
        } elseif ($size < 1048576) {
            $v = sprintf('%.02lf', $size / 1024);
            $u = ' KB';
        } elseif ($size < 1073741824) {
            $v = sprintf('%.02lf', $size / 1048576);
            $u = ' MB';
        } elseif ($size <= 2147483647) {
            $v = sprintf('%.02lf', $size / 1073741824);
            $u = ' GB';
        } elseif ($size < 1099511627776) {
            // 从这里开始，就要 64 位机器了
            $v = sprintf('%.02lf', $size / 1073741824);
            $u = ' GB';
        } elseif ($size < 1125899906842624) {
            $v = sprintf('%.02lf', $size / 1099511627776);
            $u = ' TB';
        } else {
            $v = sprintf('%.02lf', $size / 1125899906842624, 2);
            $u = ' PB';
        }
        return $v . $u;
    }

    public static function isThreadHasSeed($tid)
    {
        if (!is_numeric($tid)) {
            return false;
        }
        $res = DB::fetch_first("SELECT * FROM " . DB::table('ngpt_seed') . " WHERE tid={$tid}");
        return !empty($res);
    }

    public static function getSeedInfoBy($key, $value)
    {
        $seed = DB::fetch_first("SELECT seed_id FROM ".
            DB::table("ngpt_seed") .
            " WHERE $key='$value';");
        if (empty($seed)) {
            throw new Exception("获取种子信息失败");
        } else {
            $data = [
                $seed['seed_id'] => 0,
            ];
            $data = [
                'query_json' => json_encode($data),
            ];
            $res = PTHelper::getApiCurl("seed/info", $data, true);
            if (empty($res) || $res['result'] != 'succeed') {
                throw new Exception("种子服务器返回错误" . print_r($res['extra'], true));
            }
            return $res['extra'][0];
        }
    }
}

?>