<?php
/**
 * 常量配置文件
 */

// 接口返回code
defined('SUCCESS_CODE') || define('SUCCESS_CODE', 0);
defined('ERROR_CODE') || define('ERROR_CODE', 1);
defined('NEED_LOGIN_CODE') || define('NEED_LOGIN_CODE', 2);
defined('NOT_AUTH_CODE') || define('NOT_AUTH_CODE', 3);

// 数据库删除标记
defined('NOT_DELETE_STATUS') || define('NOT_DELETE_STATUS', 0);
defined('IS_DELETE_STATUS') || define('IS_DELETE_STATUS', 1);

// url
defined('IMG_URL') || define('IMG_URL', 'http://img.gkfk5.cn');
