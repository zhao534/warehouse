<?php
namespace Home\Controller;
use Think\Controller;
class ContractController extends \Home\Controller\IndexController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_contract = D('Contract');
    	$array = $model_contract->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			//创建模型
			$model_contract = D('Contract');
			if($model_contract->create()){
				if($model_contract->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_contract->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_contract->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_contract = D('Contract');
		
		$data = $model_contract
		->alias('a')
		->field('a.*,b.name as name_project')
		->join('wh_project b ON b.id=a.project_id')
		->where("a.id={$id}")
		->find();
		
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_contract = D('Contract');
			if($model_contract->create()){
				$result = $model_contract->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_contract->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_contract->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	//删除合同，对应工程收入，工程支出，财务表影响问题
	public function del($id){
		$model_contract = D('Contract');
		$result = $model_contract->delete($id);
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
}