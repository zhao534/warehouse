namespace <?php echo $moduleName; ?>\Controller;
use Think\Controller;
class <?php echo $tn; ?>Controller extends \Home\Controller\IndexController {
    public function index(){
    	$page_number = C('PAGE_NUMBER');
    	
    	$model_<?php echo lcfirst($tn);?> = D('<?php echo $tn; ?>');
    	$array = $model_<?php echo lcfirst($tn);?>->show_page($page_number);
    	$this->assign('str',$array["str"]);// 赋值数据集
    	$this->assign('data',$array["data"]);// 赋值分页输出
   		
    	$this->display();
    }
	
    //添加
	public function add(){
		if(IS_POST){
			//创建模型
			$model_<?php echo lcfirst($tn);?> = D('<?php echo $tn; ?>');
			if($model_<?php echo lcfirst($tn);?>->create()){
				if($model_<?php echo lcfirst($tn);?>->add()){
					$this->success("添加成功！",U("index"));
					exit;
				}
				else{
					if(APP_DEBUG){
						$sql = $model_<?php echo lcfirst($tn);?>->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			//表单验证失败
			else{
				$error = $model_<?php echo lcfirst($tn);?>->getError();
				$this->error(implode("<br/>",$error));
			}
		}
		//显示表单
		$this->display();
	}
	
	
	//编辑
	public function edit($id){
		$model_<?php echo lcfirst($tn);?> = M('<?php echo $tn; ?>');
		
		$data = $model_<?php echo lcfirst($tn);?>->find($id);
		$this->assign("data",$data);
		
		$this->display();
	}
	
	public function update(){
		if(IS_POST){
			$model_<?php echo lcfirst($tn);?> = D('<?php echo $tn; ?>');
			if($model_<?php echo lcfirst($tn);?>->create()){
				$result = $model_<?php echo lcfirst($tn);?>->save();
				if($result!==FALSE)
					$this->success("修改数据成功！",U('index'));
				else 
				{
					if(APP_DEBUG){
						$sql = $model_<?php echo lcfirst($tn);?>->getLastSql();
						$this->error("插入数据失败，SQL：".$sql."出错原因：".mysql_error());
					}
					else
						$this->error("插入数据失败，请重试");
				}
			}
			else{
				//返回一个数组，拼装成字符串
				$error = $model_<?php echo lcfirst($tn);?>->getError();
				$this->error(implode("<br/>",$error));
			}
		}
	}
	
	//删除
	public function del($id){
		$model_<?php echo lcfirst($tn);?> = M('<?php echo $tn; ?>');
		$result = $model_<?php echo lcfirst($tn);?>->delete($id);
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