<?php
namespace Home\Controller;
use Think\Controller;
class RoleController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_role = D('Role');
    	//$array = $model_role->show_page($page_number);
    	
    	$data = $model_role->get_pri_id_list_name();
    	
    	//$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$data);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加
	public function add(){
		$model_privilege = D('Privilege');
		$priData = $model_privilege->getTree();
		$this->assign("priData",$priData);
		
		if(IS_POST){
			$model_role = D('Role');
			if($model_role->create()){
				if($model_role->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_role->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_role->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_role = D('Role');
		
		$data = $model_role->find($id);
		$this->assign("data",$data);
		
		$model_privilege = D('Privilege');
		$priData = $model_privilege->getTree();
		$this->assign("priData",$priData);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_role = D('Role');
			
			if($model_role->create()){
				$result = $model_role->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_role->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_role->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_role = D('Role');
		$result = $model_role->delete($id);
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