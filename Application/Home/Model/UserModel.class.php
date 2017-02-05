<?php
namespace Home\Model;
use Think\Page;

use Think\Model;
class UserModel extends Model
{
	protected $patchValidate = true;
	
	protected  $_validate = array(
			//全部情况下都验证
			array("username","require","用户名不能为空",1,'regex',3),
			
			array("username","","用户名已经存在",0,"unique",1),
			array('role_id', 'chkfunc', '必须要选择用户角色！', 3,"callback"),
			
			array("password","require","密码不能为空",1,"regex",1),
			array("repassword","require","确认密码不能为空",1,"regex",1),
			array("password","repassword","两次密码必须一致",0,"confirm",1),
			array("password","repassword","两次密码必须一致",0,"confirm",2),
			array("username","","用户名已经存在!",1,"unique",1),
			
			array("password","require","密码不能为空",1,"regex",4),
			array("captcha","require","验证码不能为空",1,"regex",4),
			array('captcha','check_verify','验证码不正确！',1,'callback',4),
	);
	
	function check_verify($code,$id=''){
		$verify = new \Think\Verify();
		return $verify->check($code,$id);
	}
	
	public function chkfunc($value){
		if($value)
			return true;
		else return false;
	}
	
	//登录事件定义
	public function login(){
		$password = $this->password;
		
		$user = $this
		->field('a.id,a.password,a.username,b.name as role_name')
		->alias('a')
		->join('wh_role b ON b.id=a.role_id')
		->where("a.username='{$this->username}'")
		->find();
		
		if($user){
			if($user['password']==md5($password)){
				session('userid',$user['id']);
				session('username',$user['username']);
				session('rolename',$user['role_name']);
				return true;
			}
		}
		else return false;
	}
	
	//判断一个管理员是否可以访问模型控制器中的方法
	public function chkPri($userID,$moduleName,$controllerName,$actionName){
		
		//没有登录时
		if(!$userID){
			//登录方法login,getCodeImg
			if(ucfirst($moduleName)=="Home")
				if(ucfirst($controllerName)=='Index')
					if(ucfirst($actionName)=='Login' || ucfirst($actionName)=='GetCodeImg')
						return TRUE;
			return false;
		}
		//已经登录过
		else{
			//跳出检测
			//登出方法logout
			//主页面index,drag,mian,menu,top
			if(ucfirst($moduleName)=="Home")
				if(ucfirst($controllerName)=='Index')
					if(ucfirst($actionName)=='Index' 
						|| ucfirst($actionName)=='Drag' 
						|| ucfirst($actionName)=='Main'
						|| ucfirst($actionName)=='Menu'
						|| ucfirst($actionName)=='Top'
						|| ucfirst($actionName)=='Logout'
						|| ucfirst($actionName)=='login')
						return TRUE;
			
			//ajax的自动提示
			//工程名称 商品名称 型号
			//获取库存
			if(ucfirst($moduleName)=="Home")
				if(ucfirst($controllerName)=='Project' 
						|| ucfirst($controllerName)=='Goodsname' 
						|| ucfirst($controllerName)=='Typename')
					if(ucfirst($actionName)=='GetData')
						return TRUE;
			
			//ajax的自动提示 
			//获取库存
			if(ucfirst($moduleName)=="Home")
				if(ucfirst($controllerName)=='Stock')
					if(ucfirst($actionName)=='GetStock')
						return TRUE;
		}
		
		//1.先查出管理员的role_id
		if($userID){
			$role_id = $this->field('role_id')->find($userID);
			$role_id = $role_id['role_id'];
		}
		else $role_id=-1;
		
		//2.根据角色的ID取出这个角色所拥有的权限ID
		if($role_id){
			$model_role = D("Role");
			$pid = $model_role->field('pri_id_list')->find($role_id);
			$pid = $pid['pri_id_list'];
		}
		else $pid='';
		
		if($pid == '*')
			return TRUE;
		
		//3.判断当前管理员是否有访问这个方法的权限
		$priModel = M('privilege');
		
		$moduleName 	= ucfirst($moduleName);
		$controllerName = ucfirst($controllerName);
		$actionName 	= ucfirst($actionName);
		
		$count = $priModel->where(
				"id IN($pid) AND 
				module_name='{$moduleName}' AND 
				controller_name='{$controllerName}' AND 
				action_name='{$actionName}'"
				)->count();
		
// 		var_dump($priModel->getLastSql());
// 		dump($count);
		
		return $count>=1;
		
		
	}
	
	
	//获取当前用户可以访问的按钮
	public function getBtn($adminId){
		
		//1.先查出管理员的ID
		$role_id = $this->field('role_id')->find($adminId);
		$role_id = $role_id['role_id'];
		//2.根据角色的ID取出这个角色所拥有的权限ID
		$model_role = D("Role");
		$pid = $model_role->field('pri_id_list')->find($role_id);
		$pid = $pid['pri_id_list'];
		
		$model_privilege = D('privilege');
		if($pid == '*')
			$priData = $model_privilege->select();
		else
			$priData = $model_privilege->where("id IN ({$pid})")->select();
		
		
// 		dump($priData);
		
		//3.从所有权限中提取出前两级的权限
		$btn = array();
		foreach($priData as $k=>$v){
			if($v['parent_id']==0){
				foreach($priData as $kk=>$vv){
					if($vv['parent_id']==$v['id'])
						$v['children'][] = $vv;
				}
				$btn[] = $v;
			}
		}
		return $btn;	
	}
	
	//钩子函数
	protected function _before_update(&$data, $options){
		if($data['password'])
			$data['password'] = md5($data['password']);
		else 
			//希望不修改密码，如果为空就把这个字段删除。
			unset($data['password']);
	}
	
	protected function _before_insert(&$data, $options){
		$data['password'] = md5($data['password']);
	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		if($un = I('get.username')){
			$map .= " AND username LIKE '%{$un}%'";
		}
		
		$totalRows = $this->where($map)->count();
		
		$page = new Page($totalRows,$perpage);
		
		$page->setConfig("prev", "上一页");
		$page->setConfig("next", "下一页");
		$page->setConfig("first", "首页");
		$page->setConfig("last", "末页");
		
		//分页显示输出
		$str = $page->show();
		//分页数据查询
		$data = $this->alias('a')->join('wh_role b ON a.role_id=b.id')
					 ->field('a.id,a.username,a.role_id,b.id as bid,b.name as role_name')
					 ->where($map)->limit($page->firstRow.','.$page->listRows)->select();

// 		$data = $this->where($map)->limit($page->firstRow.','.$page->listRows)->select();
		
//  		dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}