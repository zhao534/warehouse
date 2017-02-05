<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_user = D("user");
    	$array = $model_user->show_page($page_number);
    	
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加管理员
	public function add(){
		if(IS_POST){
			//创建管理员模型
			$model_user = D("user");
			if($model_user->create()){
				if($model_user->add()){
					$this->success("添加新用户成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_user->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_user->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		$model_role = D('role');
		$this->assign('data_role',$model_role->field('id,name')->select());
		
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_user = D("user");
		
		$data = $model_user->find($id);
		$this->assign("data",$data);
		
		$model_role = D('role');
		$this->assign('data_role',$model_role->field('id,name')->select());
		
// 		dump($data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_user = D("user");
			if($model_user->create()){
				$result = $model_user->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_user->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_user->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_user = D("user");
		$result = $model_user->delete($id);
		if($result)
			$this->success("删除成功！",U('index'));
		else 
			$this->error("删除失败,请刷新重试！");
	}
	
	//批量删除用户
	public function bdel(){
		$did = I('post.delid');
		$str = implode(',', $did);
		
		//调用删除方法
		$this->del($str);
	}
}