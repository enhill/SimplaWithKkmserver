<?PHP

/**
 * Simpla CMS
 *
 * @copyright 	2009 Denis Pikusov
 * @link 		http://simp.la
 * @author 		Denis Pikusov
 *
 * Корзина покупок
 * Этот класс использует шаблон cart.tpl
 *
 */
 
require_once('View.php');

class CheckView extends View
{
	public function __construct()
	{
		parent::__construct();

	}

	//////////////////////////////////////////
	// Основная функция
	//////////////////////////////////////////
	function fetch()
	{
		if($url = $this->request->get('url', 'string'))
			$order = $this->orders->get_order((string)$url);
		elseif(!empty($_SESSION['order_id']))
			$order = $this->orders->get_order(intval($_SESSION['order_id']));
		else
			return false;
			
		if(!$order)
			return false;
						
		$purchases = $this->orders->get_purchases(array('order_id'=>intval($order->id)));
		if(!$purchases)
			return false;
			
		if($this->request->method('post'))
		{
			if($payment_method_id = $this->request->post('payment_method_id', 'integer'))
			{
				$this->orders->update_order($order->id, array('payment_method_id'=>$payment_method_id));
				$order = $this->orders->get_order((integer)$order->id);
			}
			elseif($this->request->post('reset_payment_method'))
			{
				$this->orders->update_order($order->id, array('payment_method_id'=>null));
				$order = $this->orders->get_order((integer)$order->id);
			}
		}
		
		$products_ids = array();
		$variants_ids = array();
		foreach($purchases as $purchase)
		{
			$products_ids[] = $purchase->product_id;
			$variants_ids[] = $purchase->variant_id;
		}
		$products = array();
		foreach($this->products->get_products(array('id'=>$products_ids)) as $p)
			$products[$p->id] = $p;
		
		$images = $this->products->get_images(array('product_id'=>$products_ids));
		foreach($images as $image)
			$products[$image->product_id]->images[] = $image;
		
		$variants = array();
		foreach($this->variants->get_variants(array('id'=>$variants_ids)) as $v)
			$variants[$v->id] = $v;
			
		foreach($variants as $variant)
			$products[$variant->product_id]->variants[] = $variant;

		foreach($purchases as &$purchase)
		{
			if(!empty($products[$purchase->product_id]))
				$purchase->product = $products[$purchase->product_id];
			if(!empty($variants[$purchase->variant_id]))
			{
				$purchase->variant = $variants[$purchase->variant_id];
			}
		}
		
		// Способ доставки
		$delivery = $this->delivery->get_delivery($order->delivery_id);
		$this->design->assign('delivery', $delivery);
			
		$this->design->assign('order', $order);
		$this->design->assign('purchases', $purchases);

		// Способ оплаты
		if($order->payment_method_id)
		{
			$payment_method = $this->payment->get_payment_method($order->payment_method_id);
			$this->design->assign('payment_method', $payment_method);
		}
			
		// Варианты оплаты
		$payment_methods = $this->payment->get_payment_methods(array('delivery_id'=>$order->delivery_id, 'enabled'=>1));
		$this->design->assign('payment_methods', $payment_methods);

		// Все валюты
		$this->design->assign('all_currencies', $this->money->get_currencies());

		
		
		// Выводим заказ
		echo $this->design->fetch('../../kkmserver/check.tpl');
		die();
	}
	

}