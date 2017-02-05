<?php
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller{
	
	public function __construct(){
		parent::__construct();	
		
		if(!session('userid'))
			if(ucfirst(MODULE_NAME)=='Home')
				if(ucfirst(CONTROLLER_NAME)=='Index'){ 
					if(ucfirst(ACTION_NAME)!='Login' && ucfirst(ACTION_NAME)!='GetCodeImg'){
						$this->error("请先登录！",U('Home/index/login'));
						die;
					}
				}
				else {
					$this->error("请先登录！",U('Home/index/login'));
					die;
				}
		
		$model_user = D("User");
		
		$result = $model_user->chkPri(session('userid'),MODULE_NAME,CONTROLLER_NAME,ACTION_NAME);
		
		if(!$result){		
			header("Content-type:text/html;charset=utf-8");
			exit("无权访问！");
		}
		
	}//__construct
}