<?php
namespace Home\Controller;
use Think\Controller;
class ProjectincomeController extends \Home\Controller\IndexController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_projectincome = D('Projectincome');
    	$array = $model_projectincome->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->display();
    }
    
    public function printresult(){
    	 
    	$model_projectincome = D('Projectincome');
    	 
    	$this->assign("data",$model_projectincome->show());
    
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			//创建模型
			$model_projectincome = D('Projectincome');
			if($model_projectincome->create()){
				
				/*****更改合同表中的未结算金额字段*******/
				$post = I('post.');
	
				$model_contract = M('Contract');
				
				//获取未结算字段、工程ID
				$contract = $model_contract
				->alias('a')
				->field('a.id AS aid,a.weijiesuan,b.id')
				->join('wh_project b ON b.id=a.project_id')
				->where("b.name='{$post['project_id']}'")
				->find();

				if($contract){
					$model_contract
					->where("id={$contract['aid']}")
					->setField(array(
							'weijiesuan'=>$contract['weijiesuan']-$post['get_money'],
					));
				}
				else{
					$this->error("没有{$post['project_id']}对应的合同记录！请确认");
					die;
				}
				/*****更改合同表中的未结算金额字段*******/
				
				if($model_projectincome->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_projectincome->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_projectincome->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		
		$model_user = D('user');
		$this->assign("data_user",$model_user->field('id,username')->select());
		
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_projectincome = D('Projectincome');
		$model_user  	     = D('User');
		
		$data = $model_projectincome
		->alias('a')
		->field('a.*,b.name as name_project')
		->join('wh_project b ON b.id=a.project_id')
		->where("a.id={$id}")
		->find();
		
		$this->assign("data_user",$model_user->field('id,username')->select());
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
// 			dump(I('post.'));
// 			die;
			$model_projectincome = D('Projectincome');
			if($model_projectincome->create()){
				
				/******************修改未结算字段******************/
				//数据改变
				//1.工程名称改变：
				//①现工程ID不存在于合同表，则不修改工程收入记录；结束程序；
				//②原工程ID修改合同表的未结算字段；
				//③现工程ID修改合同表的未结算字段；
				//2.工程名称未变，入账金额改变，修改合同表未结算字段
				$data_post = I('post.');
				$model_contract = M('contract');
				
				//查找现工程ID对应的合同表记录
				$contract = $model_contract
				->alias('a')
				->field('a.id AS aid,a.weijiesuan,a.project_id,b.id')
				->join('wh_project b ON b.id=a.project_id')
				->where("b.name='{$data_post['project_id']}'")
				->find();
				
				if(!$contract){
					$this->error("没有{$data_post['project_id']}对应的合同记录！请确认");
					die;
				}
				
				//查找原工程ID对应的合同表记录
				$contract_old = $model_contract
				->field('project_id,weijiesuan')
				->where("project_id={$data_post['project_id_old']}")
				->find();
				
				if($data_post['name_project_old'] != $data_post['project_id']){
					
					//原ID
					$model_contract
					->where("project_id={$contract_old['project_id']}")
					->setField(array(
							'weijiesuan'=>$contract_old['weijiesuan']+$data_post['get_money_old'],
					));
					
					//现ID
					$model_contract
					->where("project_id={$contract['project_id']}")
					->setField(array(
							'weijiesuan'=>$contract['weijiesuan']-$data_post['get_money'],
					));
				}
				else{
					if($data_post['get_money_old'] != $data_post['get_money']){
						$model_contract
						->where("project_id={$contract['project_id']}")
						->setField(array(
								'weijiesuan'=>$contract['weijiesuan']+$data_post['get_money_old']-$data_post['get_money'],
						));
					}
				}
				/******************修改未结算字段******************/
				
				
				
				$result = $model_projectincome->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_projectincome->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_projectincome->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		$model_projectincome = M('Projectincome');
		
		//获取工程收入和合同表的未结算金额
		$res = $model_projectincome
		->field('project_id,get_money,get_time')
		->where("id IN ({$id})")
		->select();
		
		if($res){
			//注意：此处用D方法会调用contract模型的_before_update函数！！！
			$model_contract = M('Contract');
			$model_account  = M('Account');
			
			foreach($res as $k=>$v){
				/****************修改合同表开始******************/
				//获取合同表对应的未结算字段
				$weijiesuan = $model_contract->getFieldByProject_id($v['project_id'],'weijiesuan');
				
				if($weijiesuan){
					$model_contract
					->where("project_id={$v['project_id']}")
					->setField(array(
						'weijiesuan'=>$weijiesuan+$v['get_money'],
					));
				}
				/*****************修改合同表结束*****************/
				
				/*****************修改记账表开始*****************/
				$year = substr($v['get_time'],0,4);
				$condition = "account_year={$year} AND account_type=0";
				$data_account = $model_account->where($condition)->find();
				
				if($data_account){
					$model_account
					->where($condition)
					->setField(array(
							"account_money"=>$data_account['account_money']-$v['get_money'],
					));
				}//if $data_account
				/******************修改记账表结束****************/
			}//foreach
		}
		
		$result = $model_projectincome->delete($id);
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