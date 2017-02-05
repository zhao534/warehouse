namespace <?php echo $moduleName; ?>\Model;
use Think\Model;
class <?php echo $tn; ?>Model extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
		<?php foreach ($fields as $k => $v):
				if($v['Field'] == 'id')
					continue ;
				// 如果这个字段有默认值说明这个字段可以不填，那么就跳过这个字段不要生成这个字段的验证规则
				if($v['Default'] !== NULL)
					continue ;
		?>
			array('<?php echo $v['Field']; ?>', 'require', '<?php echo $v['Comment']; ?>不能为空！', 1),
		<?php endforeach; ?>
	); 

	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		if($name = I('get.name')){
			$map .= " AND name LIKE '%{$name}%'";
		}
		
		$totalRows = $this->where($map)->count();
		
		$page = new \Think\Page($totalRows,$perpage);
		
		$page->setConfig("prev", "上一页");
		$page->setConfig("next", "下一页");
		$page->setConfig("first", "首页");
		$page->setConfig("last", "末页");
		
		//分页显示输出
		$str = $page->show();
		
		//分页数据查询
		$data = $this->where($map)->limit($page->firstRow.','.$page->listRows)->select();
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}










