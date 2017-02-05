<?php
namespace Home\Controller;
use Think\Controller;
class StockhouseController extends \Home\Controller\BaseController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_stockhouse = D('Stockhouse');
    	$array = $model_stockhouse->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
    	
//     	dump($array['data']);
    	
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			
			//创建模型
			$model_stockhouse = D('Stockhouse');
			if($model_stockhouse->create()){
				if($model_stockhouse->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_stockhouse->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_stockhouse->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		$model_user = M('user');
		$data_user = $model_user
		->field('a.id,a.username')
		->alias('a')
		->join('wh_role b ON b.id=a.role_id')
		->where("b.name='仓库管理员' OR b.name='系统管理员'")
		->select();
		
		$this->assign('data_user',$data_user);
		//显示表单
		$this->display();
	}
	
	
	//编辑用户
	public function edit($id){
		$model_stockhouse = D('Stockhouse');
		$model_user = M('user');
		
		$data = $model_stockhouse->find($id);
		$data_user = $model_user
		->field('a.id,a.username')
		->alias('a')
		->join('wh_role b ON b.id=a.role_id')
		->where("b.name='仓库管理员' OR b.name='系统管理员'")
		->select();
		
		$this->assign("data",$data);
		$this->assign('data_user',$data_user);

// 		dump($data);
// 		dump($data_user);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_stockhouse = D('Stockhouse');
			if($model_stockhouse->create()){
				$result = $model_stockhouse->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_stockhouse->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_stockhouse->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除用户 
	public function del($id){
		$model_stockhouse = D('Stockhouse');
		$result = $model_stockhouse->delete($id);
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