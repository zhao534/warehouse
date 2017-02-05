<?php
namespace Home\Model;
use Think\Model;
class PrivilegeModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('pri_name', 'require', '权限名称不能为空！', 1),
					array('parent_id', 'require', '上级ID不能为空！', 1),
					array('module_name', 'require', '对应模块名称不能为空！', 1),
					array('controller_name', 'require', '对应控制器名称不能为空！', 1),
					array('action_name', 'require', '对应方法名称不能为空！', 1),
			); 
	
	//钩子函数
	//在插入之前，将模块名称，控制器名称，方法名称首字母都大写
	public function _before_insert(&$data, $options){
		$data['module_name'] 	 = ucfirst($data['module_name']);
		$data['controller_name'] = ucfirst($data['controller_name']);
		$data['action_name'] 	 = ucfirst($data['action_name']);
	}
	
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	public function _before_delete($options){
		//先找出所有的子级权限ID并删除
		if(is_array($options['where']['id'])){
			$_allChildren = array();
			$id = explode(',', $options['where']['id'][1]);
			foreach ($id as $k=>$v){
				//把找到的子权限的ID合并到allchildren中
				$_allChildren = array_merge($_allChildren,$this->getChildren($v));
			}
			
			//去重
			$_allChildren = array_unique($_allChildren);
			if($_allChildren){
				$_allChildren = implode(',', $_allChildren);
				$this->execute("DELETE FROM wh_privilege WHERE id IN($_allChildren)");
			}
		}
		else{
			//找出所有子权限ID
			$children = $this->getChildren($options['where']['id']);
			if($children){
				$children = implode(',', $children);
				$this->execute("DELETE FROM wh_privilege WHERE id IN($children)");
			}
		}
	}
	
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
	
	//找出一个权限所有子权限的ID
	public function getChildren($id){
		$data = $this->select();
		return $this->_getChildren($data, $id,true);
	}
	
	public function _getChildren($data,$parent_id,	$isClear=false){
		static $ret = array();
		if($isClear) $ret = array();
		foreach($data as $k=>$v){
			if($v['parent_id']==$parent_id){
				$ret[] = $v['id'];
				$this->_getChildren($data,$v['id']);
			}
		}
		return $ret;
	}
	
}