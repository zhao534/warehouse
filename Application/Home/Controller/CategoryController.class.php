<?php
namespace Home\Controller;
use Think\Controller;
class CategoryController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = 10;
    	
    	$model_category = D('Category');
//     	$array = $model_category->show_page($page_number);
//     	$this->assign('str',$array["str"]);// 赋值数据集
//     	$this->assign('data',$array["data"]);// 赋值分页输出
    	$data = $model_category->getTree();
    	
    	$this->assign('data',$data);// 赋值分页输出
    	
    	
    	$this->display();
    }
	
    //添加
	public function add(){
		//创建模型
		$model_category = D('Category');
		
		if(IS_POST){
			if($model_category->create()){
				if($model_category->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_category->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_category->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		//显示父级权限
		$this->assign('data',$model_category->getTree());
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_category = D('Category');
		
		//显示所有父级权限
		$this->assign('catData',$model_category->getTree());
		
		//找出当前权限的所有子权限ID
		$children = $model_category->getChildren($id);
		$this->assign('children',$children);
		
		$data = $model_category->find($id);
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_category = D('Category');
			if($model_category->create()){
				$result = $model_category->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_category->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_category->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_category = D('Category');
		$result = $model_category->delete($id);
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