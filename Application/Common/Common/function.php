<?php 
//判读字符串是否是正整数
function IsPositiveInteger($v){
	if(is_numeric($v)){
			$v = $v+0;
			if(is_int($v) && $v>0)
				return true;
		}
		return false;
} 

//判读字符串是否是正整数或正浮点数
function IsPositiveFloat($v){
	if(is_numeric($v)){
			$v = $v+0;
			if((is_float($v) || is_int($v)) && $v>0)
				return true;
			else
				return false;
	}
	return false;
}

//判断选择框是否选择数据
function IsSelected($v){
	if($v)
		return true;
	else return false;
}

//添加后钩子函数计算支入和支出
//0：工程收入1：其他收入2：工程支出3：其他支出
function afterInsert($data,$time,$money,$account_type){
	$year = substr($data[$time],0,4);
	
	$model_account = M('account');
	
	$condition = "account_year={$year} AND account_type={$account_type}";
	
	$result = $model_account->where($condition)->find();
	
	//记账记录不存在
	if(!$result){
		//添加记录
		$model_account
		->where($condition)
		->add(array(
				'account_year'  => $year,
				'account_type'  => $account_type,
				"account_money" => $data[$money],
		));
	}
	else{
		//修改记录
		$model_account
		->where($condition)
		->setField(array(
				"account_money"=>$result['account_money']+$data[$money],
		));
	}
}

function afterUpdate($post,$time,$time_old,$money,$money_old,$account_type){
	//data数组中get_time_old字段被过滤，需使用I('post.')
	
	$year     = substr($post[$time],0,4);
	$year_old = substr($post[$time_old],0,4);
	
	$get_money     = $post[$money];
	$get_money_old = $post[$money_old];
	
	$model_account = M('account');
	
	$condition     = "account_type={$account_type} AND account_year={$year}";
	$condition_old = "account_type={$account_type} AND account_year={$year_old}";
	
	$result = $model_account
	->field('account_year,account_money')
	->where($condition)
	->find();
	
	$result_old = $model_account
	->field('account_year,account_money')
	->where($condition_old)
	->find();
	
	if( ($year==$year_old) && ($get_money!=$get_money_old) ){
		//编辑此条记录即可
		$model_account
		->where($condition)
		->setField(array(
				"account_money"=>$result['account_money']+$get_money-$get_money_old,
		));
	}
	else if($year!=$year_old){
		if($result){
			//编辑操作
			$model_account
			->where($condition)
			->setField(array(
					"account_money"=>$result['account_money']+$get_money,
			));
		}
		else{
			//添加操作
			$model_account
			->where($condition)
			->add(array(
					'account_year'  => $year,
					'account_type'  => $account_type,
					"account_money" => $get_money,
			));
		}//else
	
		//编辑原纪录
		$model_account
		->where($condition_old)
		->setField(array(
				"account_money"=>$result_old['account_money']-$get_money_old,
		));
	}//else if
}