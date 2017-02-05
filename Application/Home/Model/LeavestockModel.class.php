<?php
namespace Home\Model;
use Think\Model;
class LeavestockModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
		array('leavedate', 'require', '出库时间不能为空！', 1),
		array('user_id', 'chkfunc', '未选择出库人！', 3,"callback"),
		array('stock_id', 'chkfunc', '未选择所出库房！', 3,"callback"),
		array('project_id', 'require', '工程名称不能为空！', 1),
		array('goods_name', 'chkfunc', '商品名称不能为空！', 3,"callback"),
	    array('type_name', 'chkfunc', '型号不能为空！', 3,"callback"),
			
		//array('price_leave', 'chkfunc', '未输入商品价格！', 3,"callback"),
		array('quantity_leave', 'chkfunc', '出货数量不能为空！', 3,"callback"),

		//添加的商品不能重复，判断条件：商品名称和型号
		array('goods_name', 'chkGoodsAndType', '所选的库房中已有此商品', 3,"callback"),
			
		//出货数量不能大于库存数量
		array('quantity_leave','chkquantity_stock','出货数量不能大于库存数量',3,'callback')
	); 
	
	
	public function chkfunc($value){
		//1.出库人2.商品名称未选择、库房未选择3.商品价格、数量未选择
		if($value=="0" || in_array("0",$value) || in_array("", $value))
			return false;
		else return true;
	}
	
	public function chkGoodsAndType($v){
		$data = I('post.');

		//$data['goods_id'] == $v
		$data_type_name = $data['type_name'];
		$number = count($data_type_name);
		
		for($i=0;$i<$number;$i++){
			for($j=$i;$j<$number;$j++){
				if(($i!=$j) && ($data_type_name[$i]==$data_type_name[$j]) && ($v[$i]==$v[$j]))
					return false;
			}
		}
	}
	
	//出货数量不能大于库存数量
	public function chkquantity_stock($v){
		$data = I('post.');
		
		$model_stock = M('Stock');
		$model_leavestockdetails = M('leavestockdetails');
		
		for($i=0;$i<count($data['goods_name']);$i++){
			$condition = "b.name='{$data['goods_name'][$i]}' AND c.name='{$data['type_name'][$i]}' AND a.stock_id={$data['stock_id']}";
			
			//库存
			$number = $model_stock
			->alias('a')
			->where($condition)
			->join('wh_goodsname b ON b.id=a.id_goodsname')
			->join('wh_typename c ON c.id=a.id_typename')
			->getField('a.quantity_stock');
			
			//编辑页面
			if(isset($data['id'])){
				
				/*
				$quantity_leave = $model_leavestockdetails
				->alias('a')
				->where("b.name='{$data['goods_name'][$i]}' AND c.name='{$data['type_name'][$i]}' AND d.stock_id={$data['stock_id']}")
				->join('wh_leavestock d ON a.id_leave=d.id')
				->join('wh_goodsname b ON b.id=a.id_goodsname')
				->join('wh_typename c ON c.id=a.id_typename')
				->getField("a.quantity_leave");
				*/
				
				//原有出库数量
				$quantity_leave = $data['quantity_leave_old'][$i];
				
				if($number+$quantity_leave<$v[$i])
					return false;
			}
			//添加页面
			else{
				if($number<$v[$i])
					return false;
			}
		}
		
		return true;
	}
	
	
	//钩子函数
	//插入之前，工程名称不存在则插入
	public function _before_insert(&$data, $options){
		$post = I('post.');
		
		$model_project = D('Project');
		
		$project_name = $post['project_id'];
		
		$project = $model_project->field('id,name')->where("name='{$project_name}'")->find();
		
		//存在
		if($project)
			$data['project_id'] = $project['id'];
		//不存在则插入
		else 
			$data['project_id'] = $model_project->add(array('name'=>$project_name));
	
	}
	
	
	
	//出库表数据插入后，
	//1.进行出库明细表插入操作
	//2.库存表修改对应的库存记录
	public function _after_insert(&$data, $options){
		
		$details= array();
		$details['id_leave'] = $data['id'];

		$model_leavestockdetails = M('leavestockdetails');
		$model_stock 			 = M('stock');
		$model_goodsname  		 = M('goodsname');
		$model_typename  		 = M('typename');
		
		$data_post = I('post.');
		                           
		for($i=0;$i<count($data_post['goods_name']);$i++){
			$details['stock_id'] 	   = $data_post['stock_id'][$i];
			$details['id_goodsname']   = $data_post['goods_name'][$i];
			$details['id_typename']    = $data_post['type_name'][$i];
			$details['quantity_leave'] = $data_post['quantity_leave'][$i];
			
			/*单位
			if($data_post['unit'][$i]){
				$details['unit'] 	   = $data_post['unit'][$i];
			}
			*/
			
			
			//1.查找到goodsname和typename的ID
			$details['id_goodsname'] = $model_goodsname->getFieldByName($details['id_goodsname'],'id');
			$details['id_typename']  = $model_typename->getFieldByName($details['id_typename'],'id');
			
			//2.进行出库明细表插入操作
			$model_leavestockdetails->add($details);
			
			//3.库存表修改对应的库存记录
			$stock= array();
			//$stock['price_leave']    = $details['price_leave'];
			$stock['quantity_stock'] = $details['quantity_leave'];
			$stock['stock_id']       = $details['stock_id'];
			$stock['id_goodsname']	 = $details['id_goodsname'];
			$stock['id_typename']	 = $details['id_typename'];
			$stock['time_update']    = date("Y-m-d H:i:s",time());
			
			/*
			if(isset($details['unit']))
				$stock['unit'] = $details['unit'];
			*/
			
			//查找条件：商品ID，仓库ID，
			$v = $model_stock
			->field('id,quantity_stock')
			->where("id_goodsname={$stock['id_goodsname']} AND id_typename={$stock['id_typename']} AND stock_id={$stock['stock_id']}")
			->find();
			
			//库存表有记录，就修改库存记录信息
			if($v['id']>0){
				$stock['quantity_stock'] = $v['quantity_stock']-$stock['quantity_stock'];
				$stock['id'] 			 = $v['id'];
				$model_stock->save($stock);
			}
			//要出库的商品在库存表中不存在,则不出库
		}
	}
	
	
	public function _before_update(&$data, $options){
		$data_post = I('post.');
		
		//数据没改
		if($data_post['project_name_old'] == $data_post['project_id']){
			$data['project_id'] = $data_post['project_id_old'];
		}
		//数据改变
		else{
			$model_project = D('Project');
			
			$project_name = $data_post['project_id'];
			
			$project = $model_project->field('id,name')->where("name='{$project_name}'")->find();
			
			//存在
			if($project)
				$data['project_id'] = $project['id'];
			//不存在则插入
			else
				$data['project_id'] = $model_project->add(array('name'=>$project_name));
		}
	}
	
	
	//编辑出库单
	//先读取出库单对应的出库详情信息，删除对应的出库详情单信息，然后将编辑的信息重新添加，修改库存信息；
	//1.单纯编辑已有的商品信息、修改已有出库详情单信息、修改对应库存即可；
	//2.添加新的商品信息、添加新的出库详情单对应信息、添加新的对应库存信息；
	//3.删除已有的商品信息、删除出库详情单对应信息、删除对应库存信息。
	public function _after_update(&$data, $options){
		//现任数据
		$data_post = I('post.');
		
// 		dump($data_post);
// 		dump($data);
// 		die;
		
		$model_leavestockdetails = M('leavestockdetails');
		$model_stock = M('stock');
		
		//获取出库详情单和库存记录
		//原有数据
		$value = $model_leavestockdetails
		->alias('a')
		->field('
				a.*,
				c.quantity_stock,c.stock_id
				')
		->join('wh_leavestock b ON a.id_leave=b.id')
		->join('wh_stock c ON c.stock_id=b.stock_id AND c.id_goodsname=a.id_goodsname AND c.id_typename=a.id_typename')
		->where("a.id_leave={$data_post['id']}")
		->select();
		
		//将出库单对应的原有出库详情单记录删除
		$result = $model_leavestockdetails->where("id_leave={$data_post['id']}")->delete();
		if($result){
			
			$model_goodsname 		 = M('Goodsname');
			$model_typename 		 = M('typename');

			//删除成功
			//出库明细表数组
			$details= array();
			$details['id_leave'] = $data_post['id'];
			//将现任出库详情单数据重新添加
			for($i=0;$i<count($data_post['goods_name']);$i++){
				$details['stock_id'] 	   = $data_post['stock_id'];
				$details['id_goodsname']   = $data_post['goods_name'][$i];
				$details['id_typename']    = $data_post['type_name'][$i];
				$details['quantity_leave'] = $data_post['quantity_leave'][$i];
				
				/*单位
				if($data_post['unit'][$i]){
					$details['unit'] 	   = $data_post['unit'][$i];
				}
				*/
				
				
				//1.插入商品名称备选表和型号名称备选表
				//根据Name获取表的id值
				$id_goods = $model_goodsname->getFieldByName($details['id_goodsname'],'id');
				if($id_goods)
					$details['id_goodsname'] = $id_goods;
				else
					$details['id_goodsname'] = $model_goodsname->add(array('name'=>$details['id_goodsname']));
				
				$id_type = $model_typename->getFieldByName($details['id_typename'],'id');
				if($id_type)
					$details['id_typename'] = $id_type;
				else
					$details['id_typename'] = $model_typename->add(array('name'=>$details['id_typename']));
				
				
				//2.进行出库明细表添加操作
				$model_leavestockdetails->add($details);
				
				//3.修改对应库存记录
				$stock = array();
				$stock['stock_id'] 	     = $details['stock_id'];
				$stock['id_goodsname']	 = $details['id_goodsname'];
				$stock['id_typename']	 = $details['id_typename'];
				$stock['time_update']    = date("Y-m-d H:i:s",time());
				//$stock['quantity_leave']
					
				/*
				if(isset($details['unit'])){
					$stock['unit'] = $details['unit'];
				}
				*/
				
				
				$flag = -1;
				foreach ($value as $k=>$v)
					if( ($v['stock_id']==$details['stock_id']) && ($v['id_goodsname']==$details['id_goodsname']) && ($v['id_typename']==$details['id_typename']) )
						$flag=$k;
				
				$condition = "id_goodsname={$stock['id_goodsname']} AND id_typename={$stock['id_typename']} AND stock_id={$stock['stock_id']}";
				
				if($flag>=0){
					
					//库存记录已有，修改此记录
					//计算改变后的库存	
					$stock['quantity_stock'] = $value[$flag]['quantity_stock']+$value[$flag]['quantity_leave']-$details['quantity_leave'];
					
					$model_stock
					->where($condition)
					->setField(array(
							'quantity_stock'=>$stock['quantity_stock'],
							'time_update'	=>date("Y-m-d H:i:s",time()),
							));
					
					//unset既可以删除变量，也可以删除数组中某个单元。但要注意的是，数组不会重建索引.
					unset($value[$flag]);
				}
				//$flag==-1 //不存在，则是新出库
				else{
					
					//查找条件：商品ID，仓库ID，
					$v = $model_stock
					->field('id,quantity_stock')
					->where($condition)
					->find();
						
					//计算改变后的库存
					$stock['quantity_stock'] = $v['quantity_stock']-$details['quantity_leave'];
					$model_stock
					->where($condition)
					->setField(array(
							'quantity_stock'=>$stock['quantity_stock'],
							'time_update'	=>date("Y-m-d H:i:s",time()),
							));
						
				}
			}
			
			//删除了出库详情记录，库存恢复到原来
			if($value){
				foreach ($value as $k=>$v){
					$quantity = $v['quantity_stock']+$v['quantity_leave'];
					$model_stock
					->where("id_goodsname={$v['id_goodsname']} AND id_typename={$v['id_typename']} AND stock_id={$v['stock_id']}")
					->setField(array(
							'quantity_stock'=>$quantity,
							'time_update'	=>date("Y-m-d H:i:s",time()),
					));
				}
			}//删除计算结束
		}//result

	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//单据号
		if($order = I('get.order')){
			$map .= " AND a.id={$order}";
		}
		
		//所出仓库
		if($stock = I('get.stock')){
			$map .= " AND c.id={$stock}";
		}
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
				
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.leavedate)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(a.leavedate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(a.leavedate)";
		}
		
		//出库人
		if($user = I('get.user')){
			$map .= " AND b.username like '%{$user}%'";
		}
		
		//工程名称
		if($project = I('get.project')){
			$map .= " AND d.name like '%{$project}%'";
		}

		$totalRows = $this->where($map)->count();
		
		$page = new \Think\Page($totalRows,$perpage);
		
		$page->setConfig("prev", "上一页");
		$page->setConfig("next", "下一页");
		$page->setConfig("first","首页");
		$page->setConfig("last", "末页");
		
		//分页显示输出
		$str = $page->show();
		
		//分页数据查询
		$data =$this->alias('a')
		->field('
				a.*,
				b.id AS id_user,b.username,
				c.name AS name_stock,
				d.name AS name_project
				')
		->join('wh_user b ON a.user_id=b.id')
		->join('wh_stockhouse c ON c.id=a.stock_id AND FIND_IN_SET('.session('userid').',c.user_id_list)')
		->join('wh_project d ON d.id=a.project_id')
		->where($map)
		->limit($page->firstRow.','.$page->listRows)
		->order('a.leavedate desc')
		->select();
		
//   		var_dump($this->getLastSql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}