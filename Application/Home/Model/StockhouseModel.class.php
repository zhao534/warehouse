<?php
namespace Home\Model;
use Think\Model;
class StockhouseModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('name', 'require', '库房名称不能为空！', 1),
			); 

	//钩子函数
	//插入之前，拼接入库管理员字符串
	public function _before_insert(&$data, $options){
		$data['user_id_list'] = implode(',',$data['user_id_list']);
	}
	
	public function _before_update(&$data, $options){
		$data['user_id_list'] = implode(',',$data['user_id_list']);
	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		if($name = I('get.name')){
			$map .= " AND a.name LIKE '%{$name}%'";
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
		$data = $this
		->alias('a')
		->field('a.*,GROUP_CONCAT(b.username) AS user')
		->join('LEFT JOIN wh_user b ON FIND_IN_SET(b.id, a.user_id_list)')
		->group('a.id')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->select(); 
		
		//dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}