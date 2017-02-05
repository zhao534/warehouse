<?php
namespace Home\Model;
use Think\Model;
class RoleModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('name', 'require', '角色名称不能为空！', 1),
					array('pri_id_list', 'chkPid', '必须要选择一个权限！', 1,"callback"),
			);

	public function chkPid($pid){
		return $pid !== NULL;
	}
	
	public function _before_insert(&$data, $options){
		$data['pri_id_list'] = implode(',',$data['pri_id_list']);
	}
	
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	public function get_pri_id_list_name(){
		$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
		return $Model->query("
    			SELECT a.*,GROUP_CONCAT(b.pri_name) AS pri_name_list FROM wh_role a
    			LEFT JOIN wh_privilege b ON FIND_IN_SET(b.id, a.pri_id_list)
    			GROUP BY a.id;"
		);
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
}
