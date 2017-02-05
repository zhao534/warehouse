<?php
namespace Home\Controller;
use Think\Controller;
class PrivilegeController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_privilege = D('Privilege');
//     	$array = $model_privilege->show_page($page_number);
//     	$this->assign('str',$array["str"]);// 赋值数据集
    	$data = $model_privilege->getTree();
		
    	$this->assign('data',$data);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加
	public function add(){
		$model_privilege = D('Privilege');
		
		if(IS_POST){
			if($model_privilege->create()){
				if($model_privilege->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_privilege->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_privilege->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//显示父级权限
		$this->assign('priData',$model_privilege->getTree());
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_privilege = D('Privilege');
		
		//显示所有父级权限
		$this->assign('priData',$model_privilege->getTree());
		
		//找出当前权限的所有子权限ID
		$children = $model_privilege->getChildren($id);
		$this->assign('children',$children);
		
		$data = $model_privilege->find($id);
		$this->assign("info",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_privilege = D('Privilege');
			if($model_privilege->create()){
				$result = $model_privilege->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_privilege->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_privilege->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		//删除当前权限时，子级权限也会被一同删除
		$model_privilege = D('Privilege');
		
		$result = $model_privilege->delete($id);
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