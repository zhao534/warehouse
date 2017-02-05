<?php
namespace Gii\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index()
    {
    	if(IS_POST)
    	{
    		$tableName = I('post.tableName');
    		$moduleName = ucfirst(I('post.moduleName'));
    		/************ 1. 先创建对应的目录 ****************/
       		$cDir = './Application/'.$moduleName.'/Controller';  // 控制器目录
    		$mDir = './Application/'.$moduleName.'/Model';
    		$vDir = './Application/'.$moduleName.'/View';
    		if(!is_dir($cDir))
    			mkdir($cDir, 0777, TRUE);
    		if(!is_dir($mDir))
    			mkdir($mDir, 0777, TRUE);
    		if(!is_dir($vDir))
    			mkdir($vDir, 0777, TRUE);
    		/************ 2. 生成控制器文件 ****************/
    		// 1. 把表名转化成TP中文件名
    		$tn = $this->tableName2TpName($tableName);
    		// 2. 读控制器的模板文件并生成新的控制器
    		// 加载模板中字符串到内存并执行里面的PHP代码
    		ob_start();  // 开启缓冲区（开辟一个内存），这行之后的所有输出都会放到这块内存中
    		include('./Application/Gii/Template/Controller.tpl');
    		// 从缓冲区中读出内容并关闭清空缓冲区
    		$str = ob_get_clean();
    		file_put_contents($cDir."/{$tn}Controller.class.php", "<?php\r\n".$str);
    		/************ 3. 生成模型文件 ****************/
    		// 取出这张表所有的字段的信息
    		$db = M();  // 生成一个空的模型用来执行SQL语句
    		$sql = 'SHOW FULL FIELDS FROM '.$tableName;
    		$fields = $db->query($sql);
    		ob_start();
    		include('./Application/Gii/Template/Model.tpl');
    		// 从缓冲区中读出内容并关闭清空缓冲区
    		$str = ob_get_clean();
    		file_put_contents($mDir."/{$tn}Model.class.php", "<?php\r\n".$str);
    		/************ 4. 生成对应的三个页页：add.html,save.html,lst.html ****************/
    		// 先生成静态页所在的目录
    		$vfDir = $vDir.'/'.$tn;
    		mkdir($vfDir, 0777, TRUE);
    		// 生成add.html
    		ob_start();
    		include('./Application/Gii/Template/add.html');
    		// 从缓冲区中读出内容并关闭清空缓冲区
    		$str = ob_get_clean();
    		file_put_contents($vfDir."/add.html", $str);
    		// 生成edit.html
    		ob_start();
    		include('./Application/Gii/Template/edit.html');
    		// 从缓冲区中读出内容并关闭清空缓冲区
    		$str = ob_get_clean();
    		file_put_contents($vfDir."/edit.html", $str);
    		// 生成index.html
    		ob_start();
    		include('./Application/Gii/Template/index.html');
    		// 从缓冲区中读出内容并关闭清空缓冲区
    		$str = ob_get_clean();
    		file_put_contents($vfDir."/index.html", $str);
    		$this->success('完成！');
    		exit;
    	}
		$this->display();
    }
    public function tableName2TpName($tableName)
    {
    	// tp名称的规则：
    	//1.去掉表前缀 
    	$dp = C('DB_PREFIX');  // 从配置文件中取出当前表前缀
    	// 如果表名中有前缀就从前缀之后截取
    	if(strpos($tableName, $dp) === 0)
    	{
    		$len = strlen($dp);
    		$tableName = substr($tableName, $len);
    	}
    	//2.去掉_并_后面的单词首字母大写，如：sh_goods_images  --> GoodsImages
    	$tableName = explode('_', $tableName);
    	// 把数组中每个元素的首字母大写
    	$tableName = array_map('ucfirst', $tableName);
    	// 再把数组中每个单词拼到一起
    	return implode('', $tableName);
    }
}