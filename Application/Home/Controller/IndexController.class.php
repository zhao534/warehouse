<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends BaseController{
	public function index(){
		$this->display();
	}
    
    //头部
    public function top(){
    	//获取用户角色
    	$model_user = M('user');
    	
    	$role_name = $model_user
    	->alias('a')
    	->field('b.name')
    	->join('wh_role b ON b.id=a.role_id')
    	->where('a.id='.session('userid'))
    	->find();
    	
    	$this->assign('role_name',$role_name['name']);
    	$this->display();
    }
    
    //左侧菜单栏
    public function menu(){
    	$model_user = D('user');
    	
//     	dump(session('id'));
    	$this->assign('btn',$model_user->getBtn(session('userid')));
    	$this->display();
    }
    
    //右侧主页面
    public function main(){
    	$this->display();
    }
    
    public function main2(){
    	$this->getAccount();
    	$this->display();
    }
    
    
    private function getAccount(){
    	//财务结算
    	$model_account = M('account');
    	 
    	//获取当前的年份
    	$thisyear = date("Y");
    	//获取去年的年份
    	$lastyear = $thisyear-1;
    	 
    	$result = $model_account
    	->where("account_year={$thisyear} OR account_year={$lastyear}")
    	->select();
    	 
    	//获取当年总工程收入
    	$project_income = 0.00;
    	//获取当年总工程支出
    	$project_expense = 0.00;
    	//获取当年总其他收入
    	$other_income = 0.00;
    	//获取当年总其他支出
    	$other_expense = 0.00;
    	 
    	//去年
    	$income_last  = 0.00;
    	$expense_last = 0.00;
    	 
    	foreach($result as $k=>$v){
    		if($v['account_year']==$thisyear){
    			switch($v['account_type']){
    				//0：工程收入1：其他收入2：工程支出3：其他支出
    				case 0:
    					$project_income  += $v['account_money'];
    					break;
    				case 1:
    					$other_income    += $v['account_money'];
    					break;
    				case 2:
    					$project_expense += $v['account_money'];
    					break;
    				case 3:
    					$other_expense   += $v['account_money'];
    					break;
    			}
    		}
    		else{
    			//去年
    			switch($v['account_type']){
    				//0：工程收入1：其他收入2：工程支出3：其他支出
    				case 0:
    				case 1:
    					$income_last  += $v['account_money'];
    					break;
    				case 2:
    				case 3:
    					$expense_last += $v['account_money'];
    					break;
    			}
    		}
    	}
    	 
    	//工程收入
    	$project_income  = number_format($project_income,2,'.','');
    	//其他收入
    	$other_income    = number_format($other_income,2,'.','');
    	//工程支出
    	$project_expense = number_format($project_expense,2,'.','');
    	//其他支出
    	$other_expense   = number_format($other_expense,2,'.','');
    	
    	//计算当年总计收入
    	$total_income  = number_format($project_income+$other_income,2,'.','');
    	 
    	//计算当年总计支出
    	$total_expense = number_format($project_expense+$other_expense,2,'.','');
    	 
    	//计算余额
    	$blance = number_format($total_income-$total_expense,2,'.','');
    	 
    	//计算上年结余
    	$last_blance = number_format($income_last-$expense_last,2,'.','');
    	 
    	$this->assign('project_income',$project_income);
    	$this->assign('project_expense',$project_expense);
    	$this->assign('other_income',$other_income);
    	$this->assign('other_expense',$other_expense);
    	$this->assign('total_income',$total_income);
    	$this->assign('total_expense',$total_expense);
    	$this->assign('blance',$blance);
    	$this->assign('last_blance',$last_blance);
    }
    
    //中间侧边
    public function drag(){
    	$this->display();
    }
    
    //登录
    public function login(){
    	if(IS_POST){
    		$model_user = D('user');
    		if($model_user->create(I('post.'),4)){
    			if($model_user->login() === TRUE){
    				$this->success("登录成功！",U('Home/index/index'));
    				exit;
    			}
    			else
    				$this->error("用户名或密码错误！");
    		}
    		//表单验证失败
    		else{
    			$error = $model_user->getError();
    			$this->error(implode("<br/>",$error));
    		}
    	}
    	
    	$this->display();
    }
    
    //登出方法定义
    public function logout(){
    	session_start(); 
		session('userid',null);
		session('username',null);
		//直接访问此地址会提示先登录
		//点击登出时，调用登录页面模版，避免提示登出时还要登录系统的警告！
    	$this->display('login');
    }
    
    //验证码图片
    public function getCodeImg(){
    	
    	$config = array(
//     			'fontSize'    =>    120,    // 验证码字体大小
    			'length'      =>    3,     // 验证码位数
    			'useNoise'    =>    false, // 关闭验证码杂点
    			'useCurve'	  =>    false,
    			'codeSet'	  =>    '0123456789',
    	);
    	
    	$verify = new \Think\Verify($config);
    	
    	$verify->entry();
    }

}