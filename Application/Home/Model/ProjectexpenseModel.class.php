<?php
namespace Home\Model;
use Think\Model;
class ProjectexpenseModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('project_id', 'require', '工程名称不能为空！', 1),
					array('user_id', 'chkfunc', '未选择入账人！', 3,"callback"),
					array('summary', 'require', '摘要不能为空！', 1),
					array('out_time', 'require', '入账时间不能为空！', 1),
					array('out_money', 'require', '入账金额不能为空！', 1),
					
					array('out_money', 'chkmoney', '入账金额格式不正确！', 3,"callback",3),
					
					//添加、编辑工程收入时，验证工程名称对应的合同是否存在
					array('project_id','chkproject','找不到此工程名称对应的合同，请确认工程名称！',3,'callback',3),
			);
	
	//检测此工程名称是否有对应的合同
	public function chkproject($v){
		$model_contract = M('contract');
	
		$contract = $model_contract
		->alias('a')
		->field('a.id')
		->join('wh_project b ON b.id=a.project_id')
		->where("b.name='{$v}'")
		->find();
	
		if($contract)
			return true;
		return false;
	}
	

	//检测金额格式是否正确
	public function chkmoney($v){
		$subject = $v;
		$pattern = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
		$res = preg_match($pattern, $subject);
	
		if($res)
			return true;
		return false;
	}
	
	public function chkfunc($value){
		//1.出库人未选择
		if($value=="0" || in_array("0",$value) || in_array("", $value))
			return false;
		else return true;
	}
	
	
	//钩子函数
	//插入之前，工程名称不存在则插入
	public function _before_insert(&$data, $options){
		$model_projectincome = new ProjectincomeModel();
		$model_projectincome->_before_insert($data, $options);
	}
	
	//钩子函数：编辑
	public function _before_update(&$data, $options){
		$model_projectincome = new ProjectincomeModel();
		$model_projectincome->_before_update($data, $options);
	}
	
	
	
	public function _after_insert(&$data, $options){
		afterInsert($data, 'out_time','out_money',2);
	}
	
	public function _after_update(&$data, $options){
		afterUpdate(I('post.'), 'out_time', 'out_time_old', 'out_money', 'out_money_old', 2);
	}
	
	//不分页显示
	public function show(){
		//查询条件
		$map = 1;
	
		//工程名称
		if($name = I('get.name')){
			$map .= " AND b.name LIKE '%{$name}%'";
		}
	
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
	
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.out_time)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(a.out_time) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.out_time)";
		}
	
		//入账金额
		$min = intval(I('get.min'));
		$max = intval(I('get.max'));
	
		if($min>0){
			if($max==0)
				$map .= " AND a.out_money>={$min}";
			else if($min<$max)
				$map .= " AND a.out_money>={$min} AND a.out_money<={$max}";
		}
		else if($max>0)
			$map .= " AND a.out_money<={$max}";
	
		//数据查询
		$data = $this
		->alias('a')
		->field('a.*,b.name AS name_project')
		->join('wh_project b ON b.id=a.project_id')
		->where($map)
		->select();
	
		// 		var_dump($this->getLastSql());
	
		return $data;
	}
	
	

	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//工程名称
		if($name = I('get.name')){
			$map .= " AND b.name LIKE '%{$name}%'";
		}
		
		//摘要
		if($summary = I('get.summary')){
			$map .= " AND a.summary LIKE '%{$summary}%'";
		}
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
		
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.out_time)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(a.out_time) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.out_time)";
		}
		
		//支出金额
		$min = intval(I('get.min'));
		$max = intval(I('get.max'));
		
		if($min>0){
			if($max==0)
				$map .= " AND a.out_money>={$min}";
			else if($min<$max)
				$map .= " AND a.out_money>={$min} AND a.out_money<={$max}";
		}
		else if($max>0)
			$map .= " AND a.out_money<={$max}";
		
		
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
		->field('a.*,b.name as name_project,c.username')
		->join('wh_project b ON a.project_id=b.id')
		->join('wh_user c ON c.id=a.user_id')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->select();
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}