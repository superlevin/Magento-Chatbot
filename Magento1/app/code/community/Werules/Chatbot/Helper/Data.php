<?php
class Werules_Chatbot_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function excerpt($text, $size)
	{
		if (strlen($text) > $size)
		{
			$text = substr($text, 0, $size);
			$text = substr($text, 0, strrpos($text, " "));
			$etc = " ...";
			$text = $text . $etc;
		}
		return $text;
	}

	public function getCommandValue($text, $cmd)
	{
		if (strlen($text) > strlen($cmd))
			return substr($text, strlen($cmd), strlen($text));
		return null;
	}

	public function checkCommand($text, $cmd)
	{
		if ($cmd['command'])
		{
			$t = strtolower($text);
			if ($t == $cmd['command'])
				return true;
			else if ($cmd['alias'])
			{
				//$alias = explode(",", $cmd['alias']);
				$alias = $cmd['alias'];
				if (is_array($alias))
				{
					foreach ($alias as $al)
					{
						if (!empty($al))
							if (strpos($t, $al) !== false)
								return true;
					}
				}
			}
		}

		return false;
	}

	public function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
			return true;

		return (substr($haystack, -$length) === $needle);
	}

	public function getProductIdsBySearch($searchString)
	{
		// Code to Search Product by $searchstring and get Product IDs
		$productCollection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('*')
			->addAttributeToFilter('visibility', 4)
			->addAttributeToFilter('type_id', 'simple')
			->addAttributeToFilter(
				array(
					array('attribute' => 'sku', 'like' => '%' . $searchString .'%'),
					array('attribute' => 'name', 'like' => '%' . $searchString .'%')
				)
			);
		//->getAllIds();
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
		$productIDs = $productCollection->getAllIds();

		if (!empty($productIDs))
			return $productIDs;

		return false;
	}

	public function loadImageContent($productID)
	{
		$imagepath = Mage::getModel('catalog/product')->load($productID)->getSmallImage();
		if ($imagepath && $imagepath != "no_selection")
		{
			$absolutePath =
				Mage::getBaseDir('media') .
				DS . "catalog" . DS . "product" .
				$imagepath;

			return curl_file_create($absolutePath, 'image/jpg');
		}
		return null;
	}

	// TELEGRAM FUNCTIONS
	public function prepareTelegramOrderMessages($orderID) // TODO add link to product name
	{
		$order = Mage::getModel('sales/order')->load($orderID);
		if ($order->getId())
		{
			$message = Mage::helper('core')->__("Order") . " # " . $order->getIncrementId() . "\n\n";
			$items = $order->getAllVisibleItems();
			foreach($items as $item)
			{
				$message .= (int)$item->getQtyOrdered() . "x " .
					$item->getName() . "\n" .
					Mage::helper('core')->__("Price") . ": " . Mage::helper('core')->currency($item->getPrice(), true, false) . "\n\n";
			}
			$message .= Mage::helper('core')->__("Total") . ": " . Mage::helper('core')->currency($order->getGrandTotal(), true, false) . "\n" .
				Mage::helper('core')->__("Zipcode") . ": " . $order->getShippingAddress()->getPostcode();
			return $message;
		}
		return null;
	}

	public function prepareTelegramProdMessages($productID) // TODO add link to product name
	{
		$product = Mage::getModel('catalog/product')->load($productID);
		if ($product->getId())
		{
			if ($product->getStockItem()->getIsInStock() > 0)
			{
				$mageHelper = Mage::helper('core');
				$chatbotHelper = Mage::helper('werules_chatbot');
				$message = $product->getName() . "\n" .
					$mageHelper->__("Price") . ": " . Mage::helper('core')->currency($product->getPrice(), true, false) . "\n" .
					$chatbotHelper->excerpt($product->getShortDescription(), 60);
				return $message;
			}
		}
		return null;
	}

	public function validateTelegramCmd($cmd)
	{
		if ($cmd == "/")
			return null;
		return $cmd;
	}

	// FACEBOOK FUNCTIONS
	public function prepareFacebookProdMessages($productID) // TODO add link to product name
	{
		$product = Mage::getModel('catalog/product')->load($productID);
		if ($product->getId())
		{
			if ($product->getStockItem()->getIsInStock() > 0)
			{
				$chatbotHelper = Mage::helper('werules_chatbot');
				$message = $product->getName() . "\n" .
					$chatbotHelper->excerpt($product->getShortDescription(), 60);
				return $message;
			}
		}
		return null;
	}

	public function prepareFacebookOrderMessages($orderID) // TODO add link to product name
	{
		$order = Mage::getModel('sales/order')->load($orderID);
		if ($order->getId())
		{
			$message = Mage::helper('core')->__("Order") . " # " . $order->getIncrementId() . "\n\n";
			$items = $order->getAllVisibleItems();
			foreach($items as $item)
			{
				$message .= (int)$item->getQtyOrdered() . "x " .
					$item->getName() . "\n" .
					Mage::helper('core')->__("Price") . ": " . Mage::helper('core')->currency($item->getPrice(), true, false) . "\n\n";
			}
			$message .= Mage::helper('core')->__("Total") . ": " . Mage::helper('core')->currency($order->getGrandTotal(), true, false) . "\n" .
				Mage::helper('core')->__("Zipcode") . ": " . $order->getShippingAddress()->getPostcode();

			return $message;
		}
		return null;
	}
}