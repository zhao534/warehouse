<?php
namespace Home\Model;
use Think\Model;
class ContractModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('project_id', 'require', '工程名称不能为空！', 1),
					array('price', 'require', '合同价格不能为空！', 1),
					array('sign_time', 'require', '合同签订时间不能为空！', 1),
// 					array('weijiesuan', 'require', '未结算金额不能为空！', 1),
// 					array('note', 'require', '备注不能为空！', 1),
// 					array('weijiesuan', 'chkmoney', '未结算格式不正确！', 3,"callback",1),
					array('price', 'chkmoney', '合同价格格式不正确！', 3,"callback",3),
					
					//添加时验证是否已经有合同对应的工程名称
					//编辑时如果工程名称有修改是否和其他合同冲突
					array('project_id','chkproject','此工程名称对应的合同已经存在！',3,'callback',3),
	);
	
	//检测此工程名称是否有对应的合同
	public function chkproject($v){
		$model_contract = M('contract');
		$data = I('post.');
		
		//编辑页面
		if(isset($data['id'])){
			$id = $data['id'];
			
			$contract = $model_contract
			->alias('a')
			->field('a.id')
			->join('wh_project b ON b.id=a.project_id')
			->where("b.name='{$v}' AND a.id!={$id}")
			->find();
		}
		//添加页面
		else{
			$contract = $model_contract
			->alias('a')
			->field('a.id')
			->join('wh_project b ON b.id=a.project_id')
			->where("b.name='{$v}'")
			->find();
		}
		
		if($contract)
			return false;
		return true;
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
	
	//钩子函数
	//插入之前，工程名称不存在则插入
	public function _before_insert(&$data, $options){
		$model_projectincome = new ProjectincomeModel();
		$model_projectincome->_before_insert($data, $options);
		
		$data['weijiesuan'] = $data['price'];
	}
	
	//钩子函数：编辑
	public function _before_update(&$data, $options){
		$model_projectincome = new ProjectincomeModel();
		$model_projectincome->_before_insert($data, $options);
	}
	

	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
		
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.sign_time)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(a.sign_time) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.sign_time)";
		}
		
		//合同价格
		$min = intval(I('get.min'));
		$max = intval(I('get.max'));
		
		if($min>0){
			if($max==0)
				$map .= " AND a.price>={$min}";
			else if($min<$max)
				$map .= " AND a.price>={$min} AND a.price<={$max}";
		}
		else if($max>0)
			$map .= " AND a.price<={$max}";
		
		//未结算工程
		//工程名称
		if($weijiesuan = I('get.weijiesuan')){
			$map .= " AND a.weijiesuan>0";
		}
		
		//工程名称
		if($name = I('get.name')){
			$map .= " AND b.name LIKE '%{$name}%'";
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
		->field('a.*,b.name AS name_project,a.price-a.weijiesuan AS yifukuan')
		->join('wh_project b ON b.id=a.project_id')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->select();
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}










