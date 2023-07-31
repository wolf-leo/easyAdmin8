<?php

namespace app\admin\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;
use think\facade\Env;

/**
 * 系统日志表
 * Class SystemLogService
 * @package app\admin\service
 */
class SystemLogService
{

    /**
     * 当前实例
     * @var object
     */
    protected static $instance;

    /**
     * 表前缀
     * @var string
     */
    protected $tablePrefix;

    /**
     * 表后缀
     * @var string
     */
    protected $tableSuffix;

    /**
     * 表名
     * @var string
     */
    protected $tableName;

    /**
     * 构造方法
     * SystemLogService constructor.
     */
    protected function __construct()
    {
        $this->tablePrefix = Config::get('database.connections.mysql.prefix');
        $this->tableSuffix = date('Ym', time());
        $this->tableName   = "{$this->tablePrefix}system_log_{$this->tableSuffix}";
        return $this;
    }

    /**
     * 获取实例对象
     * @return SystemLogService|object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }


    /**
     * 保存数据
     * @param $data
     * @return bool|string
     */
    public function save($data)
    {
        Db::startTrans();
        try {
            $this->detectTable();
            Db::table($this->tableName)->insert($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 检测数据表
     * @return bool
     */
    public function detectTable(): bool
    {
        // 手动删除日志表时候 记得清除缓存
        $isset = Cache::get("systemLog{$this->tableName}Table");
        if ($isset) return true;
        $check = Db::query("show tables like '{$this->tableName}'");
        if (empty($check)) {
            $sql = $this->getCreateSql();
            Db::execute($sql);
        }
        Cache::set("system_log_{$this->tableName}_table", !empty($check));
        return true;
    }

    public function getAllTableList()
    {

    }

    /**
     * 根据后缀获取创建表的sql
     * @return string
     */
    protected function getCreateSql()
    {
        $charset = Env::get('DATABASE.CHARSET', 'utf8');
        return <<<EOT
CREATE TABLE `{$this->tableName}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned DEFAULT '0' COMMENT '管理员ID',
  `url` varchar(1500) NOT NULL DEFAULT '' COMMENT '操作页面',
  `method` varchar(50) NOT NULL COMMENT '请求方法',
  `title` varchar(100) DEFAULT '' COMMENT '日志标题',
  `content` text NOT NULL COMMENT '内容',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
  `create_time` int(10) DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET={$charset} ROW_FORMAT=COMPACT COMMENT='后台操作日志表 - {$this->tableSuffix}';
EOT;
    }

}
