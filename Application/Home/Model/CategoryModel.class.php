<?php
namespace Home\Model;
use Think\Model;
class CategoryModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('name', 'require', '分类名称不能为空！', 1),
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
	
	public function getTree(){
		$data = $this->select();
		return $this->_getTree($data,0,0,true);
	}
	
	public function _getTree($data,$parent_id=0,$level=0,$isClear=false){
		static $ret = array();
		if($isClear) $ret = array();
	
		foreach($data as $k=>$v){
			if($v['parent_id'] == $parent_id){
				$v['level'] = $level;
				$ret[] = $v;
				$this->_getTree($data,$v['id'],$level+1);
			}
		}
		return $ret;
	}
	
	//找出一个分类所有子分类的ID
	public function getChildren($id){
		$data = $this->select();
		return $this->_getChildren($data, $id,true);
	}
	
	public function _getChildren($data,$parent_id,	$isClear=false){
		static $ret = array();
		if($isClear) $ret = array();
		foreach($data as $k=>$v){
			if($v['parent_id'] == $parent_id){
				$ret[] = $v['id'];
				$this->_getChildren($data,$v['id']);
			}
		}
		return $ret;
	}
}










