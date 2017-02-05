<?php
namespace Home\Model;
use Think\Model;
class TypenameModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('name', 'require', '名称不能为空！', 1),
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










