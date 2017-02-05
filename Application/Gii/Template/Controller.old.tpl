namespace <?php echo $moduleName; ?>\Controller;
use Think\Controller;
class <?php echo $tn; ?>Controller extends Controller 
{
    public function add()
    {
		if(IS_POST)
		{
			$model = D('<?php echo $tn; ?>');
			if($model->create())
			{
				if($model->add())
				{
					$this->success('添加成功！', U('lst'));
					exit;
				}
				else 
				{
					if(APP_DEBUG)
					{
						$sql = $model->getLastSql();
						$this->error('插入数据库失败，请重试！失败的原因：SQL是：'.$sql.',出错原因：'.mysql_error());
					}
					else 
						$this->error('插入数据库失败，请重试！');
				}
			}
			else 
			{
				$error = $model->getError();
				$this->error($error);
			}
		}
    	$this->display();	
    }
    public function save($id)
    {
		if(IS_POST)
		{
			$model = D('<?php echo $tn; ?>');
			if($model->create())
			{
				if($model->save() !== FALSE)
				{
					$this->success('修改成功！', U('lst'));
					exit;
				}
				else 
				{
					if(APP_DEBUG)
					{
						$sql = $model->getLastSql();
						$this->error('修改失败，请重试！失败的原因：SQL是：'.$sql.',出错原因：'.mysql_error());
					}
					else 
						$this->error('修改失败，请重试！');
				}
			}
			else 
			{
				$error = $model->getError();
				$this->error($error);
			}
		}
		$model = M('<?php echo $tn; ?>');
		$info = $model->find($id);
		$this->assign('info', $info);
    	$this->display();	
    }
    public function lst()
    {
    	$model = D('<?php echo $tn; ?>');
    	$data = $model->search();
		$this->assign('page', $data['page']);
		$this->assign('data', $data['data']);
		$this->display();
    }
    public function del($id)
    {
    	$model = D('<?php echo $tn; ?>');
    	$model->delete($id);
    	$this->success('删除成功!');
    	exit;
    }
    public function bdel()
    {
    	$did = I('post.delid');
    	if($did)
    	{
    		$did = implode(',', $did); // 数组转化成一个字符串用，隔开：如：1,2,3
	    	$model = D('<?php echo $tn; ?>');
	    	$model->delete($did);  // 如果要删除多条记录，可以传ID为：1,2,3,4,5
    	}
    	$this->success('删除成功!');
    	exit;
    }
}