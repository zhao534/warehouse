<?php
namespace Home\Model;
use Think\Model;
class LeavestockdetailsModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
			array('id_leave', 'require', '出库单编号不能为空！', 1),
			array('id_leave', 'chkorder', '出库单号不存在，请确认！', 3,"callback",3),
				
			//只添加操作时验证
			array('id_leave', 'chkorder_goods_type', '数据库中已有此记录，不用再添加！', 3,"callback",1),
				
			array('id_goodsname', 'require', '必须要填写商品名称！', 1),
			array('id_typename', 'require', '必须要填写型号名称', 1),
							
			array('quantity_leave', 'require', '出货数量不能为空！', 1),
			array('quantity_leave', 'chkquantity', '出货数量必须大于0而且是整数！', 3,"callback"),
	);
	
	//检测数据库中是否有此单号，没有则报错
	public function chkorder($v){
		if($this->field('id')->where("id_leave={$v}")->select())
			return true;
		return false;
	}
	
	//入库详细表中有相同的单号、商品名称、型号（三个条件），则不插入，报错
	public function chkorder_goods_type($v){
		$post = I("post.");
		$id_goodsname = $post['id_goodsname'];
		$id_typename = $post['id_typename'];
	
		$res = $this
		->alias('a')
		->field('a.id')
		->where("a.id_leave={$v} AND b.name='{$id_goodsname}' AND c.name='{$id_typename}'")
		->join("wh_goodsname b ON b.id=a.id_goodsname")
		->join("wh_typename c ON c.id=a.id_typename")
		->select();
	
		if($res)
			return false;
		return true;
	}
	
	public function chkgoods($v){
		return isSelected($v);
	}
	
	public function chkstock($v){
		return isSelected($v);
	}
	
	public function chkquantity($v){
		return IsPositiveInteger($v);
	}
	
	public function chkprice($v){
		return IsPositiveFloat($v);
	}
	
	
	//钩子函数
	//在出库明细表插入前，进行名称备选表和型号备选表操作
	public function _before_insert(&$data, $options){
		$post = I('post.');
		
		$model_goodsname = M('Goodsname');
		$model_typename  = M('typename');
	
		//根据Name获取表的id值
		$id_goods = $model_goodsname->getFieldByName($post['id_goodsname'],'id');
	
		if($id_goods)
			$data['id_goodsname'] = $id_goods;
		else
			$data['id_goodsname'] = $model_goodsname->add(array('name'=>$post['id_goodsname']));
	
		//根据Name获取表的id值
		$id_type = $model_typename->getFieldByName($post['id_typename'],'id');
		if($id_type)
			$data['id_typename'] = $id_type;
		else
			$data['id_typename'] = $model_typename->add(array('name'=>$post['id_typename']));
		
		//单位字段设置默认值，如果用户没有输入，则使用默认值。
		if(empty($data['unit']))
			unset($data['unit']);
	}
	
	
	//入库明细表插入后，库存表修改对应的库存记录
	public function _after_insert(&$data, $options){
		$data_post = I('post.');

		$model_stock = M('stock');
	
		$details= array();
		$details['quantity_stock'] = $data['quantity_leave'];
		$details['id_goodsname']   = $data['id_goodsname'];
		$details['id_typename']    = $data['id_typename'];
		$details['time_update']    = date("Y-m-d H:i:s",time());
		//$details['stock_id']
	
		//获取仓库索引
		$stock_id = $this
		->alias('a')
		->field('b.stock_id')
		->join('wh_leavestock b ON b.id=a.id_leave')
		->where("a.id_leave={$data['id_leave']} AND a.id_goodsname={$data['id_goodsname']} AND a.id_typename={$data['id_typename']}")
		->find();
	
		$details['stock_id'] = $stock_id['stock_id'];
	
		//修改库存记录信息
		//查找条件：商品ID，仓库ID，
		$v = $model_stock
		->field('id,quantity_stock')
		->where("id_goodsname={$details['id_goodsname']} AND id_typename={$details['id_typename']} AND stock_id={$details['stock_id']}")
		->find();
	
		if($v['id']>0){
			$details['quantity_stock']  = $v['quantity_stock']-$details['quantity_stock'];
			$details['id'] 				= $v['id'];
			$model_stock->save($details);
		}
		//else if($v['id']==NULL)
	}
	
	//读取goodsname和typename的id
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	//更新库存
	public function _after_update(&$data, $options){
		$data_post = I('post.');
	
		$model_stock = M('stock');
	
		$details= array();
		$details['quantity_stock'] = $data_post['quantity_leave'];
		$details['stock_id']       = $data_post['stock_id'];
		//此处就得用$data['id_goodsname']，不能用$data_post['id_goodsname_old']
		$details['id_goodsname']   = $data['id_goodsname'];
		$details['id_typename']    = $data['id_typename'];
		$details['time_update']    = date("Y-m-d H:i:s",time());
	
		//计算库存
		//查找条件：商品名称，型号，仓库ID
		$v = $model_stock
		->field('id,quantity_stock')
		->where("id_goodsname={$details['id_goodsname']} AND id_typename={$details['id_typename']} AND stock_id={$details['stock_id']}")
		->find();
	
		//如果商品名称或型号有变更，就读取原有信息
		if( ($data['id_goodsname']!=$data_post['id_goodsname_old']) || ($data['id_typename']!=$data_post['id_typename_old']) ){
			$p =$model_stock
			->field('id,quantity_stock')
			->where("id_goodsname={$data_post['id_goodsname_old']} AND id_typename={$data_post['id_typename_old']} AND stock_id={$details['stock_id']}")
			->find();
		}
	
		if($v['id']>0){
			//变更的不是商品自身属性
			if(isset($p)==false){
				//【当前库存数量】=【原有库存】-【当前修改过的出货数量】+【修改前的出货数量】
				$details['quantity_stock']  = $v['quantity_stock']-$details['quantity_stock']+$data_post['quantity_old'];
				$details['id'] 				= $v['id'];
				$model_stock->save($details);
			}
			//商品属性变更了
			else{
				//【变更商品的库存数量】=【原有库存】-【修改过的出货数量】
				$model_stock->save(array(
						'quantity_stock' => $v['quantity_stock']-$data_post['quantity_leave'],
						'id'			 => $v['id'],
				));
				//【以前商品的库存数量】=【原有库存】+【提交前的出货数量】
				$model_stock->save(array(
						'quantity_stock' => $p['quantity_stock']+$data_post['quantity_old'],
						'id'			 => $p['id'],
				));
			}
		}
		//else if($v['id']==NULL)
		
	}
	
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//搜索条件：
		//订单号
		if($order = I('get.order')){
			$map .= " AND a.id_leave={$order}";
		}
	
		//商品库房
		if($stock = I('get.stock')){
			$map .= " AND f.id={$stock}";
		}
		
		//商品分类
		//搜索时显示此分类下所有的
		/*
		if($cat = I('get.cat')){
			//获取当前分类的子分类
			$model_category = D('category');
			$children = $model_category->getChildren($cat);
		
			if($children){
				$children = implode(',', $children);
				//加上当前分类
				$str = $cat.','.$children;
			}
			else
				$str = $cat;
				
			$map .= " AND d.id IN({$str})";
		}
		*/
		
		//时间段
		if($end = I('get.end')){
			$start = I('get.start');
			
			if(empty($start))
				$map .= " AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.leavedate)";
			else
				$map .= " AND UNIX_TIMESTAMP('{$start}')<=UNIX_TIMESTAMP(b.leavedate) AND UNIX_TIMESTAMP('{$end}')>=UNIX_TIMESTAMP(b.leavedate)";
		}
		
		//数量
		$min = intval(I('get.min'));
		$max = intval(I('get.max'));
		
		if($min>0){
			if($max==0)
				$map .= " AND a.quantity_leave>={$min}";
			else if($min<$max)
				$map .= " AND a.quantity_leave>={$min} AND a.quantity_leave<={$max}";
		}
		else if($max>0)
			$map .= " AND a.quantity_leave<={$max}";
		
		//商品类型
		if($type = I('get.type')){
			$map .= " AND e.name LIKE '%{$type}%'";
		}
		
		//商品名称
		if($name = I('get.name')){
			$map .= " AND d.name LIKE '%{$name}%'";
		}
		
		//工程名称
		if($project = I('get.project')){
			$map .= " AND c.name LIKE '%{$project}%'";
		}
		
		$totalRows = $this->where($map)->count();
		
		$page = new \Think\Page($totalRows,$perpage);
		
		$page->setConfig("prev", "上一页");
		$page->setConfig("next", "下一页");
		$page->setConfig("first", "首页");
		$page->setConfig("last", "末页");
		
		//分页显示输出
		$str = $page->show();
		
		//分页数据查询
		$data =$this->alias('a')
		->field('
				a.*,
				b.leavedate,
				c.name as name_project,
				d.name as name_goods,
				e.name as name_type,
				f.name as name_stockhouse,
				g.price_enter
				')
		->join('wh_leavestock b ON a.id_leave=b.id')
		->join('wh_project c ON c.id=b.project_id')
		->join('wh_goodsname d ON a.id_goodsname=d.id')
		->join('wh_typename e ON a.id_typename=e.id')
		->join('wh_stockhouse f ON b.stock_id=f.id AND FIND_IN_SET('.session('userid').',f.user_id_list)')
		->join('wh_stock g ON g.id_goodsname=a.id_goodsname AND g.id_typename=a.id_typename AND g.stock_id=b.stock_id')
		//排列根据出库单据号和出库时间
		->order("a.id_leave desc,b.leavedate asc")
		->where($map)->limit($page->firstRow.','.$page->listRows)->select();
		
//  	var_dump($this->getLastSql());
		
// 		dump($data);
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
	
}