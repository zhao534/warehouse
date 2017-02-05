<?php
return array(
	//'配置项'=>'配置值'

	//配置数据库
	//PDO连接方式
	// 数据库类型
	'DB_TYPE'   => 'pdo', 
	// 用户名
	'DB_USER'   => 'root', 
	// 密码
	'DB_PWD'    => '', 
	// 数据库表前缀
	'DB_PREFIX' => 'wh_',  
	'DB_DSN'    => 'mysql:host=localhost;dbname=warehouse;',
		
	// 系统默认的变量过滤机制
    'DEFAULT_FILTER' => 'trim,htmlspecialchars',

	//分页页数
	'PAGE_NUMBER' => 20,
);