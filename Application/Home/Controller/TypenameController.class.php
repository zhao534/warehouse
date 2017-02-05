<?php
namespace Home\Controller;
use Think\Controller;
class TypenameController extends \Home\Controller\IndexController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_typename = D('Typename');
    	$array = $model_typename->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			//创建模型
			$model_typename = D('Typename');
			if($model_typename->create()){
				if($model_typename->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_typename->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_typename->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_typename = M('Typename');
		
		$data = $model_typename->find($id);
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_typename = D('Typename');
			if($model_typename->create()){
				$result = $model_typename->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_typename->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_typename->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		$model_typename = M('Typename');
		$result = $model_typename->delete($id);
		if($result)
			$this->success("删除成功！",U('index'));
		else 
			$this->error("删除失败,请刷新重试！");
	}
	
	//批量删除
	public function bdel(){
		$did = I('post.delid');
		$str = implode(',', $did);
		
		//调用删除方法
		$this->del($str);
	}
	
	public function getData($term){
		$model_typename = M('typename');
		$name = $model_typename->field('name')->where("name LIKE '%{$term}%'")->select();
		return $this->ajaxReturn($name);
	}
}