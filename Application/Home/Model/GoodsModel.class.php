<?php
namespace Home\Model;
use Think\Model;
class GoodsModel extends Model 
{
	protected $patchValidate = true;
	
	protected $_validate = array(
					array('name', 'require', '商品名称不能为空！', 3),
					array('type_id', 'chkfunc', '必须要选择商品规格！', 3,"callback"),
					array('cat_id', 'chkfunc', '必须要选择商品分类！', 3,"callback"),
					//array('pic', 'require', '商品图片不能为空！', 1),
					//array('unit', 'require', '单位不能为空！', 1),
					//array('stock_id', 'chkfunc', '必须要选择所属库房！', 3,"callback"),
			); 
	
	public function chkfunc($value){
		if($value)
			return true;
		else return false;
	}
	
	//钩子函数
	//单位字段设置默认值，如果用户没有输入，则使用默认值。
	public function _before_insert(&$data, $options){
		if(empty($data['unit']))
			unset($data['unit']);
	}
	
	public function _before_update(&$data, $options){
		$this->_before_insert($data, $options);
	}
	
	//分页显示
	public function show_page($perpage){
		//查询条件
		$map = 1;
		
		//搜索条件：
		//商品库房
		/*
		if($stock = I('get.stock')){
			$map .= " AND c.id={$stock}";
		}
		*/
		
		//商品分类
		//搜索时显示此分类下所有的
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
		
		//商品类型
		if($type = I('get.type')){
			$map .= " AND b.name LIKE '%{$type}%'";
		}
		
		//商品名称
		if($name = I('get.name')){
			$map .= " AND a.name LIKE '%{$name}%'";
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
				a.id as goods_id,a.name,a.type_id,a.cat_id,a.pic,a.price,a.unit,
				b.id,b.name as name_type,
				d.id,d.name as name_category
				')
		->join('wh_type b ON a.type_id=b.id')
		//->join('wh_stockhouse c ON a.stock_id=c.id')
		->join('wh_category d ON a.cat_id=d.id')
		->where($map)->limit($page->firstRow.','.$page->listRows)->select();
		
		//var_dump($this->getlastsql());
		
		return array(
				"data" => $data,
				"str"  => $str
				);
	}
}