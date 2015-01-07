<?php


    class IB_IbSales_Model_Order_Item extends Mage_Sales_Model_Order_Item
    {

        CONST ProductPricePercent    = 0;
        CONST FulfillmentChargeValue = 10;

        public $totalPaymentDesigner = 0;
        /** @var $collectionProductByDesigner Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        private $_collectionProductByDesigner = null;
        private $_productCollection = null;
        /** @var $_fulfillmentChargeValueForDesigner array  - used for changed FulfillmentChargeValue for designer */
        private  $_fulfillmentChargeValueForDesigner = array('Sequence' => 5);
        protected $_arrTotal = array();
        protected $_orderIdsCollect = array();
        protected $_orderItemSkus = array();
        protected $_currentQty = 0;


        public $arrRD = array(
            '181' => array('481' => '22.5', '328' => '22.5', '278' => '22.5', '510' => '22.5', '459' => '22.5', '387' => '22.5'),
            '132' => array('371' => '17.5', '328' => '17.5', '278' => '17.5', '281' => '17.5'),
        );

        public function getArrRD()
        {
            return $this->arrRD;
        }

        public $arrRDCost = array(
            '181' => array('710' => '77.5', '706' => '77.5', '499' => '77.5', '195' => '77.5', '707' => '77.5', '312' => '77.5', '332' => '77.5', '697' => '77.5', '867' => '77.5', '649' => '77.5', '476' => '77.5', '648' => '77.5', '709' => '77.5', '586' => '77.5', '596' => '77.5', '698' => '77.5'),
            '132' => array('648' => '77.5', '368' => '77.5', '505' => '77.5', '395' => '77.5', '699' => '75'),
            '180' => array('335' => '55', '332' => '55', '746' => '55', '476' => '55', '329' => '55'),
        );

        public function getArrRDCost()
        {
            return $this->arrRDCost;
        }

        public function isReturnable()
        {

        }

        public function isExchangeable()
        {

        }

        protected function _getProduct()
        {
            if (is_null($this->getProduct())) {
                // loading product
                $product = Mage::getModel('catalog/product')->load($this->getProductId());
                if ($product->getId()) {
                    $this->setProduct($product);
                }
            }
            return $this->getProduct();
        }

        /**
         * getProduct
         * @return Mage_Catalog_Model_Product
         */
        public function getProductItrem()
        {
            return $this->_getProduct();
        }


        public function getLowestCategoryLabel()
        {
            if (!is_null($this->_getProduct())) {

				$cats = $this->_getProduct()->getLowestCategoryLabel(); 

				return $cats;
            }
            return '';
        }

        public function getProductTrend()
        {
            if (!is_null($this->_getProduct())) {
                return $this->_getProduct()->getLowestCategoryLabel();
            }
            return '';
        }


        public function getTopCategoryLabel()
        {
			
            if (!is_null($this->_getProduct())) {
				return $this->_getProduct()->getTopCategoryLabelReport();
            }
            return '';
        }


        public function getNetTotal()
        {
            //return $this->getRowTotal() - $this->getDiscountAmount();
            $value = $this->getBaseRowTotal() - $this->getBaseDiscountAmount();
            if (!$value) {
                $value = 0;
            }
            return number_format(round($value, 2), 2, '.', '');
        }

        public function getCostTotal()
        {
            $result = 0;
            $refunded = 0;
            $orderedItem = 0;
            // workaround for reports....

            $cost = $this->getProductCostValue();

            if (!$cost) {
                $cost = 0;
            }

            /*
            if($this->getData('cost') > 0) {
                $cost = $this->getData('cost');
            } else {
                $cost = $this->getProductCostValue();
            }*/

            if (isset($this->_orderItemSkus['refunded_qty'][$this->getSku()])){
                /** @var  $refunded  - for Total Product Sales*/
                $refunded = $this->_orderItemSkus['refunded_qty'][$this->getSku()];
                $orderedItem = $this->getTotalQty();
            } else{
                /** @var  $refunded  for Customers by orders report*/
                $refunded = $this->getQtyRefunded();
                $orderedItem = $this->getQtyOrdered();
            }

            $result = ($cost * $orderedItem) - ($refunded * $cost);

            return number_format(round($result, 2), 2, '.', '');
        }


        public function getProductCostValueCode()
        {
            // once again workaround
            $cost = 0;
            $pcv  = $this->getData('product_cost_value');
            if ($this->getData('cost') > 0) {
                $cost = $this->getData('cost');
            } elseif (is_numeric($pcv) && ($pcv > 0)) {
                $cost = $pcv;
            }

            return number_format(round($cost, 2), 2, '.', '');
            //return $cost;
        }

        /**
         * Fulfillment Charge * Units Sold
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getFulfCharge()
        {
            $value = 0;
            if ($this->getProduct()->getProductFulfillmentCharge() == 1) {
                $value = $this->getFulfillmentChargeValue() * $this->getTotalQty();
            }

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getFulfChargeCOR()
        {
            $value = 0;
            if ($this->getProductFulfillmentCharge() == 1) {
                $value = $this->getFulfillmentChargeValue() * $this->getQtyOrdered();
            }

            return number_format(round($value, 2), 2, '.', '');

        }

        public function getItemShippingCharge()
        {
            $charge = 0;
            if (isset($this->_orderItemSkus['shipment_charge'][$this->getSku()])){
            $charge = $this->_orderItemSkus['shipment_charge'][$this->getSku()];
        }

            return number_format(round($charge, 2), 2, '.', '');
        }

        public function getProfit()
        {
            $value = 0;

            $getNetTotal = $this->getNetTotal();

            $retailerId = $this->getProductRetailerId();
            $designerId = $this->getProductDesignerValue();

            if ($retailerId && $designerId && isset($this->arrRD[$retailerId]) && isset($this->arrRD[$retailerId][$designerId])) {
                $value = $getNetTotal - $this->getCostTotal() - (($this->arrRD[$retailerId][$designerId] / 100) * $getNetTotal);
            } else {
                $value = $getNetTotal - $this->getCostTotal();
            }

            /*$product_consigment = $this->getProductConsigment();
            $product_profit_formula = $this->getProductProfitFormula();
            $product_profit_formula_percent = $this->getProductProfitFormulaPercent();

            //Old formula with BaseRowTotal
            //$value = $this->getBaseRowTotal() - $this->getCostTotal();
            //New formula with NetTotal
            $value = $getNetTotal - $this->getCostTotal();

            if($product_consigment=='OH Stock'){
                $value = $getNetTotal - $this->getCostTotal();
            }

            if(($product_consigment=='Ven Consignment' || $product_consigment=='OH Consign') && !empty($product_profit_formula) && !empty($product_profit_formula_percent)){
                switch($product_profit_formula){
                    case 'Percentage Formula':
                        $value = $getNetTotal * ($product_profit_formula_percent/100);
                    break;
                    case 'Cost Formula':
                        $value = $getNetTotal + (($product_profit_formula_percent/100) * ($getNetTotal - $this->getCostTotal()));
                    break;
                    default:
                        $value = $getNetTotal - $this->getCostTotal();
                }
            }*/

            $value += $this->getFulfCharge();

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getDiscount()
        {
            //$discount = ($this->getRetailPrice() - $this->getPrice())*$this->getQtyOrdered() + $this->getDiscountAmount();
            $discount = $this->getBaseDiscountAmount();
            return number_format(round($discount, 2), 2, '.', '');
        }

        public function getRetailTotalPrice()
        {
            $value = 0;

            if ($this->getMarkdown() < 0.01) {
                $value = $this->getBaseRowTotal();
            }

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getRetailPrice()
        {
            $value = 0;

            if ($this->getMarkdown() < 0.01) {
                $value = $this->getBaseRowTotal() / $this->getQtyOrdered();
            }

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getMarkdown()
        {
            $markdownPrice = 0;

            $price = $this->getBaseRowTotal() / $this->getQtyOrdered();
            if ($this->getProductOrigPrice() - $price > 0) {
                $markdownPrice = $this->getBaseRowTotal();
            }

            return number_format(round($markdownPrice, 2), 2, '.', '');
        }


        /**
         * Net Total Sales - Cost + Fulfillment Charge
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getClearProfit()
        {
            return number_format(round($this->getNetTotalAmountSales() - $this->getCostTotal() + $this->getFulfCharge(), 2), 2, '.', '');
        }

        public function getClearProfitCOR()
        {
            return number_format(round($this->getNetTotalAmountSalesCOR() - $this->getCostTotal() + $this->getFulfChargeCOR(), 2), 2, '.', '');
        }

        /**
         * Check - purchased Does the product at a special price
         *
         * Using in - CUSTOMER BY ORDER REPORT, TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getSpecialPriceInPurchase()
        {
            if ($this->getPurchasePriceProduct() < $this->getRetailPriceInPurchase()){
                return $this->getPurchasePriceProduct();
            }

            return $this->getEmptyValue();
        }

        /**
         * Retail product price
         *
         * Using in - CUSTOMER BY ORDER REPORT, TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getRetailPriceInPurchase()
        {
            return number_format(round($this->getProductOrigPrice(), 2), 2, '.', '');
        }


        /**
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getPurchasePriceProduct()
        {
            return number_format(round($this->getPrice(), 2), 2, '.', '');;
        }


        /**
         * Total Regular Sales
         * Retail Price * Units Sold if empty Special Price
         *
         * Using in - CUSTOMER BY ORDER REPORT, CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getTotalRegularSales()
        {
            if ($this->getSpecialPriceInPurchase() === $this->getEmptyValue()){
                return number_format(round($this->getRetailPriceInPurchase() * $this->getTotalQty(), 2), 2, '.', '');
            }

            return $this->getEmptyValue();
        }

        public function getTotalRegularSalesCOR()
        {
            if ($this->getSpecialPriceInPurchase() === $this->getEmptyValue()){
                return number_format(round($this->getRetailPriceInPurchase() * $this->getQtyOrdered(), 2), 2, '.', '');
            }

            return $this->getEmptyValue();
        }

        /**
         * Special Price * Units Sold (this is 0 if no special price)
         *
         * Using in - CUSTOMER BY ORDER REPORT, "CUSTOMER BY ORDER REPORT"
         *
         * @return string
         */
        public function getTotalMarkdownSales()
        {
            $orderedItem = 0;
            if ($orderedItem = $this->getTotalQty()){
                /** @var  $refunded  - for Total Product Sales*/
                $orderedItem = $this->getTotalQty();
            } else{
                /** @var  $refunded  for Customers by orders report*/
                $orderedItem = $this->getQtyOrdered();
            }

            if ($this->getSpecialPriceInPurchase() !== $this->getEmptyValue()){
                return number_format(round($this->getSpecialPriceInPurchase() * $orderedItem, 2), 2, '.', '');
            }

            return $this->getEmptyValue();
        }

        /**
         * Discount Amount from Promo Code
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getDiscountAmountPromoCode()
        {
            return number_format(round($this->getDiscountAmount(), 2), 2, '.', '');
        }

        /**
         * Total Sales
         * Total Regular Sales + Total Markdown Sales - Total Discount to Sale
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getTotalAmountSales()
        {
            return number_format(round($this->getTotalRegularSales() + $this->getTotalMarkdownSales() - $this->getDiscountAmount(), 2), 2, '.', '');
        }


        public function getTotalAmountSalesCOR()
        {
            return number_format(round($this->getTotalRegularSalesCOR() + $this->getTotalMarkdownSales() - $this->getDiscountAmount(), 2), 2, '.', '');
        }

        /**
         * Total Sales
         * Total Regular Sales + Total Markdown Sales - Total Discount to Sale
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getTotalAmountSalesByDesigner()
        {
            return number_format(round($this->getTotalRegularSalesAll() + $this->getTotalMarkdownSalesAll() - $this->getTotalDiscountAll(), 2), 2, '.', '');
        }

        /**
         * Total Sales - Refunded
         *
         * Using in - CUSTOMER BY ORDER REPORT
         *
         * @return string
         */
        public function getNetTotalAmountSales()
        {
            return number_format(round($this->getTotalAmountSales() - $this->getRefundAmount(), 2), 2, '.', '');
        }

        public function getNetTotalAmountSalesCOR()
        {
            return number_format(round($this->getTotalAmountSalesCOR() - $this->getRefundAmountCOR(), 2), 2, '.', '');
        }

        /**
         * Total Sales - Refunded
         *
         * Using in - CUSTOMER BY ORDER REPORT, TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getNetTotalAmountSalesItem()
        {
            return number_format(round($this->getTotalAmountSales() - $this->getRefundAmount(), 2), 2, '.', '');
        }

        /**
         *
         * Using in - CUSTOMER BY ORDER REPORT, TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getGrossTotalSales()
        {
            if ($this->getOrderTaxAmount() === null){
                return number_format(round($this->getNetTotalAmountSalesItem(), 2), 2, '.', '');
            } else {
                return number_format(round($this->getNetTotalAmountSalesItem() + $this->getTotalOrderShippingAmount() + $this->getOrderTaxAmount(), 2), 2, '.', '');
            }
        }

        /*public function getRetailPrice(){
            $retPrice = 0;

            if($this->getQtyOrdered() != 0){
                $diff = ($this->getRowTotal()/$this->getQtyOrdered()) - $this->getProductOrigPrice();
                if($diff > 0){
                    $retPrice = $this->getProductOrigPrice() + $diff;
                }else{
                    $retPrice = $this->getProductOrigPrice();
                }
            }

            //return number_format(round($retPrice, 2),2);
            return $retPrice;
        }*/

        /**
         * Just a replacement for standart functionality. It should place value to "amount_refunded" field of item
         */
        public function getRefundAmount()
        {
//            $refund = '0.00';
////            $refund = $this->getQtyRefunded() * $this->getBasePrice();
//            if ($this->getTotalQty() != 0){
//                $refund = ($this->getTotalAmountSales() / $this->getTotalQty()) * $this->getQtyRefunded();
//            }
            $this->getArrTotal();
            $charge = 0;
            if (isset($this->_orderItemSkus['refunded'][$this->getSku()])){
                $charge = $this->_orderItemSkus['refunded'][$this->getSku()];
            }

            return number_format(round($charge, 2), 2, '.', '');
        }

        public function getRefundAmountCOR()
        {
            $refund = '0.00';
            $refund = ($this->getTotalAmountSalesCOR() / $this->getQtyOrdered()) * $this->getQtyRefunded();
            return number_format(round($refund, 2), 2, '.', '');

        }

            /**
         * Check - purchased Does the product at a special price
         *
         * Using in - CUSTOMER BY ORDER REPORT, TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getItemRefunded()
        {
            $refund = '0.00';
            if ($this->getQtyOrdered() != 0 && $this->getQtyRefunded() != 0){
                $refund = ($this->getTotalRegularSales() - $this->getDiscountAmountPromoCode() / $this->getQtyRefunded());
            }

            return number_format(round($refund, 2), 2, '.', '');
        }


        public function getNameDesignerRetailer()
        {
			
            $user     = Mage::getModel('admin/user')->load($this->getProductRetailerId());
            $designer = $this->getProductDesigner();
            if (empty($designer)) {
                $designer = 'No Designer';
            }

            if (Mage::getModel('retailers/retailer')->isSpecialRetailer($this->getProductRetailerId())) {
                $name = $designer . ' (' . $user->getName() . ')';
            } else {
                $name = $designer . ' (L-atitude Shop)';
            }
			
            return $name;
        }

        public function getNameLocationRetailer()
        {
            $user     = Mage::getModel('admin/user')->load($this->getProductRetailerId());
            $location = $this->getProductLocation();
            if (empty($location)) {
                $location = 'No Location';
            }
            if (Mage::getModel('retailers/retailer')->isSpecialRetailer($this->getProductRetailerId())) {
                $name = $location . ' (' . $user->getName() . ')';
            } else {
                $name = $location . ' (L-atitude Shop)';
            }

            return $name;
        }


        /*#########################################################################*/
        /***************************Report For Designer*************************************/
        /*#########################################################################*/

        public function getRetailName()
        {
            $user = Mage::getModel('admin/user')->load($this->getProductRetailerId());
            return $user->getFirstname() . ' ' . $user->getLastname();
        }

        public function getPercentFromRetailerPrice()
        {
            $ibReportRetailFilter = Mage::registry('ibReportRetailFilter');
            $perc                 = self::ProductPricePercent;
            $value                = 0;

            $product_consigment = $this->getProductConsigment();

            if ($product_consigment == 'OH Stock' || $this->getRefundAmountCOR() > 0) {
                return number_format(round($value, 2), 2, '.', '');
            }

            //if(isset($ibReportRetailFilter['perc_of_retail']) && ($ibReportRetailFilter['perc_of_retail'] == 0 || !empty($ibReportRetailFilter['perc_of_retail']))){
            if (isset($ibReportRetailFilter['perc_of_retail'])) {
                $perc = $ibReportRetailFilter['perc_of_retail'];
            }

            if ($perc != 0) {
                if ($perc != -1) {
                    $value = (($this->getSalePrice() / 100) * $perc) * $this->getQtyOrdered();
                } else {
                    $value = $this->getQtyOrdered() * $this->getProductCostValue();
                }
            }

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getFulfillmentCharge()
        {
            $isProductFulfillmentCharge = $this->getProductFulfillmentChargeValue();
            if ($isProductFulfillmentCharge) {
                return $this->getFulfillmentChargeValue() * $this->getQtyOrdered();
            } else {
                return 0;
            }
        }

        public function getFulfillmentChargeValue()
        {

            $designers = array_keys($this->_fulfillmentChargeValueForDesigner);
            if (in_array($this->getProductDesigner(), $designers)){
                return $this->_fulfillmentChargeValueForDesigner[$this->getProductDesigner()];
            }

            return self::FulfillmentChargeValue;
        }

        public function getPaymentDesigner()
        {
            $value                = 0.0001;
            $ibReportRetailFilter = Mage::registry('ibReportRetailFilter');
            $product_consigment   = $this->getProductConsigment();

            if ($product_consigment == 'OH Stock' || $this->getRefundAmountCOR() > 0) {
                return number_format(round($value, 2), 2, '.', '');
            }

            if (isset($ibReportRetailFilter['perc_of_retail']) && $ibReportRetailFilter['perc_of_retail'] == 0) {
                $totalCost = $this->getCostTotal();
                if (!empty($totalCost)) {
                    $value = $totalCost;
                }
            } else {
                $value = $this->getPercentFromRetailerPrice() - $this->getFulfillmentCharge();
            }

            return number_format(round($value, 2), 2, '.', '');

        }


        public function getSalePrice()
        {
            return number_format(round($this->getBasePrice(), 2), 2, '.', '');
        }

        /**
         * Get Total sales by designer
         * @return string
         */
        public function getTotalSalesBD()
        {
            return number_format(round($this->getBaseRowTotal(), 2), 2, '.', '');
        }

        public function getRetailPriceRD()
        {
            $diff = ($this->getBaseRowTotal() / $this->getQtyOrdered()) - $this->getProductOrigPrice();
            if ($diff > 0) {
                $retPrice = $this->getProductOrigPrice() + $diff;
            } else {
                $retPrice = $this->getProductOrigPrice();
            }

            return number_format(round($retPrice, 2), 2, '.', '');
        }


        /*#########################################################################*/
        /***************************Report By Designer/Destination/Category*************************************/
        /*#########################################################################*/


        public function getArrTotal()
        {
            if (empty($this->_arrTotal)) {
				
                $collection = Mage::getResourceModel('sales/order_item_collection');


                $product_designer = Mage::getResourceSingleton('catalog/product')->getAttribute('product_designer');
                $product_location = Mage::getResourceSingleton('catalog/product')->getAttribute('product_location');
                $product_category = Mage::getResourceSingleton('catalog/product')->getAttribute('product_category');

                $order_grand_total              = Mage::getResourceSingleton('sales/order')->getAttribute('base_grand_total');
                $product_cost                   = Mage::getResourceSingleton('catalog/product')->getAttribute('cost');
                $product_orig_price             = Mage::getResourceSingleton('catalog/product')->getAttribute('price');
                $product_consigment             = Mage::getResourceSingleton('catalog/product')->getAttribute('product_consigment');
                $product_profit_formula         = Mage::getResourceSingleton('catalog/product')->getAttribute('product_profit_formula');
                $product_profit_formula_percent = Mage::getResourceSingleton('catalog/product')->getAttribute('product_profit_formula_percent');
                $product_fulfillment_charge     = Mage::getResourceSingleton('catalog/product')->getAttribute('product_fulfillment_charge');
                $product_retailer               = Mage::getResourceSingleton('catalog/product')->getAttribute('retailer_id');

                // joining order info
                $collection->getSelect()
                    ->joinLeft(array('table_order' => 'sales_order'),
                        'table_order.entity_id=main_table.order_id',
                        array('order_increment_id' => 'table_order.increment_id', 'order_base_total_paid' => 'table_order.base_total_paid')
                    )

                    ->joinLeft(array('table_order_grand_total' => $order_grand_total->getBackend()->getTable()),
                        'table_order_grand_total.entity_id=main_table.order_id ',
                        array('order_grand_total' => 'base_grand_total')
                    )

                    ->joinLeft(array('table_product_designer_value' => $product_designer->getBackend()->getTable()),
                        'table_product_designer_value.entity_id=main_table.product_id and table_product_designer_value.attribute_id=' . (int)$product_designer->getAttributeId(),
                        array('product_designer_value' => 'value')
                    )

                    ->joinLeft(array('table_product_designer' => 'eav_attribute_option_value'),
                        'table_product_designer.option_id=table_product_designer_value.value',
                        array('product_designer' => 'value')
                    )

                    ->joinLeft(array('table_product_location_value' => $product_location->getBackend()->getTable()),
                        'table_product_location_value.entity_id=main_table.product_id and table_product_location_value.attribute_id=' . (int)$product_location->getAttributeId(),
                        array('product_location_value' => 'value')
                    )

                    ->joinLeft(array('table_product_location' => 'eav_attribute_option_value'),
                        'table_product_location.option_id=table_product_location_value.value',
                        array('product_location' => 'value')
                    )

                    ->joinLeft(array('table_product_category_value' => $product_category->getBackend()->getTable()),
                        'table_product_category_value.entity_id=main_table.product_id and table_product_category_value.attribute_id=' . (int)$product_category->getAttributeId(),
                        array('product_category_value' => 'value')
                    )
                    ->joinInner(array('table_product_category' => 'eav_attribute_option_value'),
                        'table_product_category.option_id=table_product_category_value.value',
                        array('product_category' => 'value')
                    )

                    ->joinLeft(array('table_product_cost_value' => $product_cost->getBackend()->getTable()),
                        'table_product_cost_value.entity_id=main_table.product_id and table_product_cost_value.attribute_id=' . (int)$product_cost->getAttributeId(),
                        array('product_cost_value' => 'value')
                    )

                    ->joinLeft(array('table_product_orig_price_value' => $product_orig_price->getBackend()->getTable()),
                        'table_product_orig_price_value.entity_id=main_table.product_id and table_product_orig_price_value.attribute_id=' . (int)$product_orig_price->getAttributeId(),
                        array('product_orig_price' => 'value')
                    )

                    ->joinLeft(array('table_product_consigment_value' => $product_consigment->getBackend()->getTable()),
                        'table_product_consigment_value.entity_id=main_table.product_id and table_product_consigment_value.attribute_id=' . (int)$product_consigment->getAttributeId(),
                        array('product_consigment_value' => 'value')
                    )
                    ->joinLeft(array('table_product_consigment' => 'eav_attribute_option_value'),
                        'table_product_consigment.option_id=table_product_consigment_value.value',
                        array('product_consigment' => 'value')
                    )

                    ->joinLeft(array('table_product_profit_formula_value' => $product_profit_formula->getBackend()->getTable()),
                        'table_product_profit_formula_value.entity_id=main_table.product_id and table_product_profit_formula_value.attribute_id=' . (int)$product_profit_formula->getAttributeId(),
                        array('product_profit_formula_value' => 'value')
                    )
                    ->joinLeft(array('table_product_profit_formula' => 'eav_attribute_option_value'),
                        'table_product_profit_formula.option_id=table_product_profit_formula_value.value',
                        array('product_profit_formula' => 'value')
                    )

                    ->joinLeft(array('table_product_profit_formula_percent_value' => $product_profit_formula_percent->getBackend()->getTable()),
                        'table_product_profit_formula_percent_value.entity_id=main_table.product_id and table_product_profit_formula_percent_value.attribute_id=' . (int)$product_profit_formula_percent->getAttributeId(),
                        array('product_profit_formula_percent' => 'value')
                    )

                    ->joinLeft(array('table_product_fulfillment_charge_value' => $product_fulfillment_charge->getBackend()->getTable()),
                        'table_product_fulfillment_charge_value.entity_id=main_table.product_id and table_product_fulfillment_charge_value.attribute_id=' . (int)$product_fulfillment_charge->getAttributeId(),
                        array('product_fulfillment_charge' => 'value')
                    )

                    ->joinLeft(array('table_product_retailer_value' => $product_retailer->getBackend()->getTable()),
                        'table_product_retailer_value.entity_id=main_table.product_id and table_product_retailer_value.attribute_id=' . (int)$product_retailer->getAttributeId(),
                        array('product_retailer_id' => 'value')
                    )


                    ->joinInner(null, null, array('order_shipping_amount' => 'table_order.base_shipping_amount'))
                    ->joinInner(null, null, array('order_tax_amount' => 'table_order.base_tax_amount'));

                //if(Mage::registry('_is_filter_product')){
                //	$collection->addFieldToFilter('product_type', 'simple');
                //}else{
                $collection->addFieldToFilter('parent_item_id', array('null' => 1));
                $collection->getSelect()->where('table_order.base_total_paid > 0');
                //}
				
				$filter = Mage::registry('ibReportFilter');
				
				//should be investigated11111111111111111111111111111111111111111111
                if (isset($filter['by_attr'])) {

                    switch ($filter['by_attr']) {
                        case 'designer':

                            $designerId = $this->getProductDesignerValue();
                            if (!empty($designerId)) {
                                //$collection->addFieldToFilter('table_product_designer_value.value', array('eq' => $designerId));
								$collection->getSelect()->where('table_product_designer_value.value = '. $designerId);
                            } else {
                                $collection->addFieldToFilter('table_product_designer_value.value', array('null' => true));
                            }

							

                            if (Mage::registry('_is_filter_product')) {
                                //print_r($this->getProductId());
								$collection->getSelect()->where('main_table.product_id = '. $this->getProductId());
                                //$collection->addFieldToFilter('main_table.product_id', array('eq' => $this->getProductId()));
                            }

                            break;
                        case 'location':
                            $locationId = $this->getProductLocationValue();
                            if ($locationId) {
                                $collection->addFieldToFilter('table_product_location_value.value', array('eq' => $locationId));
                            } else {
                                $collection->addFieldToFilter('table_product_location_value.value', array('null' => true));
                            }
                            break;
                        case 'category':
                            $collection->addFieldToFilter('table_product_category_value.value', array('finset' => Mage::registry('_item_category_id')));
                            break;
                        default:
                            $collection->addFieldToFilter('table_product_designer_value.value', array('eq' => $this->getProductDesignerValue()));

                        //$collection->addFieldToFilter('table_product_category_value.value', array('finset'=>80));
                    }
                }

			
				/*$t1 = microtime(true);
                $this->_arrTotal['total_units_all_period'] = 0;
                $collection_2                              = clone $collection;
                foreach ($collection_2 as $coll) {
                    $this->_arrTotal['total_units_all_period'] += $coll->getQtyOrdered();
                }
				Mage::log('Item - total_units_all_period getTotal:' . (microtime(true) - $t1), true, 'report.log' );*/
				

                if (isset($filter['report_from']) && ($filter['report_to'])) {
                    try {
                        $from = Mage::app()->getLocale()->date($filter['report_from'], Zend_Date::DATE_SHORT, null, false);
                        $from->add(date('H:i:s', (-1) * Mage::getModel('core/date')->getGmtOffset()), Zend_Date::TIMES);
                        $from = $from->get(Varien_Date::DATETIME_INTERNAL_FORMAT);
                        $to   = strtotime($filter['report_to'] . ' 23:59:59') + (-1) * Mage::getModel('core/date')->getGmtOffset();
                        $to   = Mage::app()->getLocale()->date(date('m/d/Y', $to), Zend_Date::DATE_SHORT, null, false);
                        $to->add(date('H:i:s', (-1) * Mage::getModel('core/date')->getGmtOffset()), Zend_Date::TIMES);
                        $to = $to->get(Varien_Date::DATETIME_INTERNAL_FORMAT);


                        /*$from = Mage::app()->getLocale()->date($filter['report_from'], Zend_Date::DATE_SHORT, null, false)->get(Varien_Date::DATE_INTERNAL_FORMAT);
                        $to   = Mage::app()->getLocale()->date($filter['report_to'], Zend_Date::DATE_SHORT, null, false);
                        $to->add('23:59:59', Zend_Date::TIMES);
                        $to = $to->get(Varien_Date::DATETIME_INTERNAL_FORMAT);*/

                        // Setting filter
                        // In order to speedup it, requesting IDs of orders first
                        $orderCollection = Mage::getModel('sales/order')
                            ->getCollection()
                            ->addFieldToFilter('created_at', array('from' => $from, 'to' => $to,));
                        $orderIds        = $orderCollection->getAllIds();

                        $collection->addFieldToFilter('order_id', array('in' => $orderIds));

                    } catch (Exception $e) {
                        $this->_errors[] = Mage::helper('reports')->__('Invalid date specified');
                    }

                }
				
				
                $this->_arrTotal['regular_sales']        = 0;
                $this->_arrTotal['markdown_sales']       = 0;
                $this->_arrTotal['total_sales_latitude'] = 0;
                //$this->_arrTotal['total_profit']         = 0;
                $this->_arrTotal['total_profit_tps']         = 0;
                $this->_arrTotal['discount_on_sale']     = 0;
                $this->_arrTotal['total_refunded']       = 0;
                $this->_arrTotal['current_qty']          = 0;
                $this->_arrTotal['total_cost']           = 0;
                $this->_arrTotal['total_cost_tps']           = 0;
                $this->_arrTotal['total_qty_ordered']           = 0;
                //$this->_arrTotal['retail_value']          = 0;
                $this->_arrTotal['total_regular_sales']  = 0;
                $this->_arrTotal['total_markdown_sales']  = 0;
                $this->_arrTotal['discount_amount']  = 0;
                $this->_arrTotal['total_order_shipping_amount']  = 0;
                $this->_arrTotal['total_order_tax_amount']  = 0;
                $this->_arrTotal['total_gross_total_sales']  = 0;
                $this->_arrTotal['total_product_fulfillment_charge']  = 0;
                //$this->_arrTotal['total_retail_value']  = 0;
                $this->_arrTotal['profit']  = 0;
                $arrCurrentQtyIds = array();
                $this->_orderIdsCollect = array_flip($collection->getColumnValues('order_id'));
                $this->_orderItemSkus['shipment_charge'] = array_fill_keys($collection->getColumnValues('sku'), 0);
                $this->_orderItemSkus['refunded'] = array_fill_keys($collection->getColumnValues('sku'), 0);
                $this->_orderItemSkus['refunded_qty'] = array_fill_keys($collection->getColumnValues('sku'), 0);
                $this->_orderItemSkus['order_shipping'] = array_fill_keys($collection->getColumnValues('sku'), 0);
				
                foreach ($collection as $coll) {
                    $totalReportDiscount = $this->getTRDiscount($coll);
                    if ($totalReportDiscount > 0) {
                        //print_r('RowTotal:'); print_R($coll->getRowTotal());print_r('<br />');
                        $this->_arrTotal['markdown_sales'] += $coll->getBaseRowTotal();
                        $this->_arrTotal['discount_on_sale'] += $totalReportDiscount;
                    } else {
                        $this->_arrTotal['regular_sales'] += $coll->getBaseRowTotal();
                    }

                    $this->_arrTotal['total_cost'] += (($coll->getProductCostValue() * $coll->getQtyOrdered()) - ($coll->getProductCostValue() * $coll->getQtyRefunded()));
                    $this->_arrTotal['total_qty_ordered'] += $coll->getQtyOrdered() - $coll->getQtyRefunded();
                    //$this->_arrTotal['total_retail_value'] += $coll->getProductCostValue() * $coll->getQty();

                    //$this->_arrTotal['retail_value'] += $coll->getFinalPrice() * $coll->getQty();
                    $this->_arrTotal['sku'][] = $coll->getSku();
                   // $this->_arrTotal['total_profit'] += $this->getTRProfit($coll);
                    if ($this->getTotalQty()){
                        $this->_arrTotal['total_refunded'] += ($coll->getQtyRefunded() * $coll->getBaseRowTotal()) - $coll->getDiscountAmount();
                    }
                    $this->_arrTotal['discount_amount'] += $coll->getDiscountAmount();

                    if ($coll->getProductOrigPrice() <= $coll->getPrice()){
                        $this->_arrTotal['total_regular_sales'] += $coll->getProductOrigPrice() * $coll->getQtyOrdered();
                    } else {
                        $this->_arrTotal['total_markdown_sales'] += $coll->getPrice() * $coll->getQtyOrdered();
                    }
                    $this->_arrTotal['total_amount_sales'] = $this->_arrTotal['total_regular_sales'] + $this->_arrTotal['total_markdown_sales'] - $this->_arrTotal['discount_amount'];
                    $this->_arrTotal['net_total_amount_sales'] = $this->_arrTotal['total_amount_sales'] - $this->_arrTotal['total_refunded'];
                    /** ORDER SHIPPING AMOUNT for Total Product Sales */
                    if (array_key_exists($coll->getOrderId(), $this->_orderIdsCollect)){
                        $this->_arrTotal['total_order_tax_amount'] += $coll->getOrderTaxAmount();
                        unset($this->_orderIdsCollect[$coll->getOrderId()]);
                    }
                    $this->_arrTotal['total_order_shipping_amount'] += $coll->getOrderShippingAmount();

                    /** Shipping Charge for Item */
                    if (array_key_exists($coll->getSku(), $this->_orderItemSkus['shipment_charge'])){
                        $this->_orderItemSkus['shipment_charge'][$coll->getSku()] += $coll->getOrderShippingAmount();
                    }

                    /** Refunded Item */
                    if (array_key_exists($coll->getSku(), $this->_orderItemSkus['refunded']) && $coll->getQtyRefunded() > 0){
                        $this->_orderItemSkus['refunded'][$coll->getSku()] += $coll->getQtyRefunded() * ($this->getTotalAmountSales() / $this->getTotalQty());
                        $this->_orderItemSkus['refunded_qty'][$coll->getSku()] += $coll->getQtyRefunded();
                    }


                    $this->_arrTotal['total_gross_total_sales'] = $this->_arrTotal['net_total_amount_sales'] + $this->_arrTotal['total_order_shipping_amount'] + $this->_arrTotal['total_order_tax_amount'];
                    if ($coll->getProductFulfillmentCharge()){
                        $this->_arrTotal['total_product_fulfillment_charge'] += $this->getFulfillmentChargeValue() * $coll->getQtyOrdered();
                    }
                    $this->_arrTotal['profit'] = $this->_arrTotal['net_total_amount_sales'] - $this->_arrTotal['total_cost'] + $this->_arrTotal['total_product_fulfillment_charge'];

                    /**************************QTy for selected period******************************/
                    $prodOption = $coll->getProdOptions();
                    if (isset($prodOption['simple_sku']) && !empty($prodOption['simple_sku'])) {
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $prodOption['simple_sku']);
                        if ($product && $prodOption['simple_sku'] == $product->getSku()) {
                            $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                            $id  = $product->getId();
                        } else {
                            $product = Mage::getModel('catalog/product')->load($coll->getProductId());
                            $qty     = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                            $id      = $product->getId();
                        }
                    } else {
                        $product = Mage::getModel('catalog/product')->load($coll->getProductId());
                        $qty     = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                        $id      = $product->getId();
                    }

                    if (!in_array($id, $arrCurrentQtyIds)) {
                        $arrCurrentQtyIds[] = $id;
                        $this->_arrTotal['current_qty'] += $qty;
                    }
                    /**************************QTy for selected period******************************/
                }

				

                //$totalLatitude = $this->getTotalLatitude();
                //$this->_arrTotal['total_sales_latitude'] = $totalLatitude['revenue'];
//                $this->_arrTotal['total_sales_latitude'] = $this->getTotalLatitude();
                $this->_arrTotal['total_sales_all'] = $this->_arrTotal['regular_sales'] + $this->_arrTotal['markdown_sales'] - $this->_arrTotal['discount_on_sale'];

                //print_r($this->_arrTotal['total_sales_latitude']);

            }
			
            return $this->_arrTotal;
        }

        public function getTRProfit($item)
        {
            $itemCostTotal = $item->getProductCostValue() * $item->getQtyOrdered();

            //$NetTotal = $this->getNetTotal();
            $NetTotal = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();

            $retailerId = $item->getProductRetailerId();
            $designerId = $item->getProductDesignerValue();

            if ($retailerId && $designerId && isset($this->arrRD[$retailerId]) && isset($this->arrRD[$retailerId][$designerId])) {
                $value = $NetTotal - $itemCostTotal - (($this->arrRD[$retailerId][$designerId] / 100) * $NetTotal);
            } else {
                $value = $NetTotal - $itemCostTotal;
            }

            /*$product_consigment = $item->getProductConsigment();
            $product_profit_formula = $item->getProductProfitFormula();
            $product_profit_formula_percent = $item->getProductProfitFormulaPercent();

            //old formula with BaseRowTotal
            //$value = $item->getBaseRowTotal() - $itemCostTotal;
            //new formula with NetTotal
            $value = $NetTotal - $itemCostTotal;

            if($product_consigment=='OH Stock'){
                $value = $NetTotal - $itemCostTotal;
            }

            if(($product_consigment=='Ven Consignment' || $product_consigment=='OH Consign') && !empty($product_profit_formula) && !empty($product_profit_formula_percent)){
                switch($product_profit_formula){
                    case 'Percentage Formula':
                        $value = $NetTotal * ($product_profit_formula_percent/100);
                    break;
                    case 'Cost Formula':
                        $value = $itemCostTotal + (($product_profit_formula_percent/100) * ($NetTotal - $itemCostTotal));
                    break;
                    default:
                        $value = $NetTotal - $itemCostTotal;
                }
            }*/
            $value -= ($item->getProductCostValue() * $item->getQtyRefunded());

            if ($item->getProductFulfillmentCharge() == 1) {
                $value += $this->getFulfillmentChargeValue() * $item->getQtyOrdered();
            }

            return number_format(round($value, 2), 2, '.', '');
        }

        public function getTotalProfit()
        {

            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['profit'], 2), 2, '.', '');
        }

        public function getTotalProfitPs()
        {
            return number_format(round($this->getNetTotalSales() - $this->getTotalCost(), 2), 2, '.', '');
        }

        /**
         * @return string
         */
        public function getTotalCost()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_cost'], 2), 2, '.', '');
        }

        /**
         * @return mixed
         */
        public function getTotalQtyOrdered()
        {
            $arrTotal = $this->getArrTotal();
            return $arrTotal['total_qty_ordered'];
        }

        /**
         * @return mixed
         */
//        public function getTotalRetailValue()
//        {
//            $arrTotal = $this->getArrTotal();
//            return number_format(round($arrTotal['total_retail_value'], 2), 2, '.', '');
//        }

        /**
         * @return string
         */
        public function getTotalFulfCharge()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_product_fulfillment_charge'], 2), 2, '.', '');
        }

        public function getRegularSales()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['regular_sales'], 2), 2, '.', '');
        }

        /**
         *
         * Using in - TOTAL PRODUCT SALES
         * @return string
         */
        public function getNetTotalAmountSalesByDesigner()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['net_total_amount_sales'], 2), 2, '.', '');
        }

        public function getProfitTotal()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_profit'], 2), 2, '.', '');
        }

        /**
         * Using in - TOTAL PRODUCT SALES
         * @return mixed
         */
        public function getTotalUnitsSold()
        {
            return number_format($this->getTotalQty() - $this->_orderItemSkus['refunded_qty'][$this->getSku()], 0) ;
        }

        /**
         * Using in - TOTAL PRODUCT SALES
         * @return mixed
         */
        public function getItemRetailValue()
        {
            return number_format($this->getTotalQty() * $this->getPrice(), 0) ;
        }

        public function getTotalRegularSalesAll()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_regular_sales'], 2), 2, '.', '');
        }

        public function getTotalMarkdownSalesAll()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_markdown_sales'], 2), 2, '.', '');
        }

        public function getTotalDiscountAll()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['discount_amount'], 2), 2, '.', '');
        }

        public function getMarkdownPrice()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['markdown_sales'], 2), 2, '.', '');
        }

        public function getDiscountOnSale()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['discount_on_sale'], 2), 2, '.', '');
        }

        public function getTotalSales()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round(($arrTotal['regular_sales'] + $arrTotal['markdown_sales'] - $arrTotal['discount_on_sale']), 2), 2, '.', '');
        }

        public function getNetTotalSales()
        {
            return number_format(round($this->getRowTotal() - $this->getTotalRefunded(), 2), 2, '.', '');
        }

        public function getRegSalesToTTLSales()
        {
            if ($this->getTotalSalesLatitude() != 0) {
                $value = ($this->getRegularSales() / $this->getTotalSalesLatitude()) * 100;
                return number_format(round($value, 2), 2, '.', '');
            } else {
                return number_format(round(0, 2), 2, '.', '');
            }
        }

        public function getMDSalesToTTLSales()
        {
            if ($this->getTotalSalesLatitude() != 0) {
                $value = ($this->getMarkdownSales() / $this->getTotalSalesLatitude()) * 100;
                return number_format(round($value, 2), 2, '.', '');
            } else {
                return number_format(round(0, 2), 2, '.', '');
            }
        }

        public function getSalesToTTLSales()
        {
            if ($this->getTotalSalesLatitude() != 0) {
                $value = ($this->getRowTotal() / $this->getTotalSalesLatitude()) * 100;
                return number_format(round($value, 2), 2, '.', '');
            } else {
                return number_format(round(0, 2), 2, '.', '');
            }
        }

        /**
         * @return string
         */
        public function getTotalRefunded()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_refunded'], 2), 2, '.', '');
        }

        /**
         * @return string
         */
        public function getTotalOrderShippingAmount()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_order_shipping_amount'], 2), 2, '.', '');
        }

        /**
         * @return string
         */
        public function getTotalOrderTaxAmount()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_order_tax_amount'], 2), 2, '.', '');
        }

        /**
         * @return string
         */
        public function getTotalGrossTotalSales()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_gross_total_sales'], 2), 2, '.', '');
        }

        public function getTotalSalesAll()
        {
            $arrTotal = $this->getArrTotal();
            return number_format(round($arrTotal['total_sales_all'], 2), 2, '.', '');
        }

        public function getTotalSalesLatitude()
        {
            $arrTotal = $this->getArrTotal();
            return $arrTotal['total_sales_latitude'];
        }

        public function getTotalGrossTotalSalesCOR()
        {
            return number_format(round($this->getNetTotalAmountSalesCOR() + $this->getOrderTaxAmount() + $this->getOrderShippingAmount(), 2), 2, '.', '');
        }

        public function getCostItem()
        {
            return number_format(round($this->getProductCostValue(), 2), 2, '.', '');
        }

        //Old SellThrough
        /*public function getSellThrough(){
            $arrTotal = $this->getArrTotal();
            if($arrTotal['total_units_all_period'] != 0 && ($arrTotal['total_units_all_period'] - $this->getTotalQty()) != 0){
                $value = ($this->getTotalQty()/($arrTotal['total_units_all_period'] - $this->getTotalQty()))*100;
                return number_format(round($value, 2),2,'.','');
            }else{
                return number_format(round(0, 2),2,'.','');
            }
        }*/


        public function getSellThrough()
        {
            $arrTotal           = $this->getArrTotal();
            $beginningInventory = $this->getBeginningInventory();
            if ($beginningInventory != 0) {
                $value = ($this->getTotalQty() / $beginningInventory) * 100;
                return number_format(round($value, 2), 2, '.', '');
            } else {
                return number_format(round(0, 2), 2, '.', '');
            }
        }

        public function getBeginningInventoryTotal()
        {
            $inv      = 0;
            $arrTotal = $this->getArrTotal();
            $this->getCollectionProductByDesigner(null, 'sku', array('nin', $arrTotal['sku']));
            $qty = $this->getCurrentInventory();
            $this->setResetCollectionProductByDesigner();
//            $inv = $this->getTotalQty() + $arrTotal['current_qty'];

            return $this->getTotalQty() + $qty;
        }

        public function getBeginningInventory()
        {
            $inv      = 0;
            $arrTotal = $this->getArrTotal();
            $inv      = $this->getTotalQty() + $arrTotal['current_qty'];
            return $inv;
        }

        public function getCurrentInventoryCs()
        {
            $arrTotal = $this->getArrTotal();
            return $arrTotal['current_qty'];
        }

        public function getTRDiscount($item)
        {
            //return ($this->getTRRetailPrice($item) - $item->getPrice())*$item->getQtyOrdered() + $item->getDiscountAmount();
            return ($this->getTRRetailPrice($item) - $item->getBasePrice()) * $item->getQtyOrdered();
        }

        public function getTRRetailPrice($item)
        {
            $retPrice = 0;

            if ($this->getQtyOrdered() != 0) {
                $diff = ($item->getBaseRowTotal() / $item->getQtyOrdered()) - $item->getProductOrigPrice();
                if ($diff > 0) {
                    $retPrice = $item->getProductOrigPrice() + $diff;
                } else {
                    $retPrice = $item->getProductOrigPrice();
                }
            }

            return $retPrice;
        }

        public function getTotalLatitude()
        {
            $collection = Mage::registry('ibReportSalesTotalByPeriod');
            $total      = 0;

            if ($collection) {
                foreach ($collection as $coll) {
                    //getRowTotal is base_row_total in this case
                    $total += $coll->getRowTotal();
                }
            }

            return $total;
        }

        /*public function getTotalLatitude(){

            $isFilter = Mage::app()->getRequest()->getParam('store') || Mage::app()->getRequest()->getParam('website') || Mage::app()->getRequest()->getParam('group');

                $collection = Mage::getResourceModel('reports/order_collection')
                ->calculateTotals($isFilter);

                if (Mage::app()->getRequest()->getParam('store')) {
                    $collection->addAttributeToFilter('store_id', Mage::app()->getRequest()->getParam('store'));
                } else if (Mage::app()->getRequest()->getParam('website')){
                    $storeIds = Mage::app()->getWebsite(Mage::app()->getRequest()->getParam('website'))->getStoreIds();
                    $collection->addAttributeToFilter('store_id', array('in' => $storeIds));
                } else if (Mage::app()->getRequest()->getParam('group')){
                    $storeIds = Mage::app()->getGroup(Mage::app()->getRequest()->getParam('group'))->getStoreIds();
                    $collection->addAttributeToFilter('store_id', array('in' => $storeIds));
                }

                $collection->load();
                $collectionArray = $collection->toArray();
                $totals = array_pop($collectionArray);

            return $totals;
        }*/

        public function getTTLSalesToTTLDestination()
        {
            $location_id = $this->getProductLocationValue();

            if (!$location_id) {

                $prCollection = Mage::getResourceModel('catalog/product_collection')
                    ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                    ->addAttributeToFilter('product_designer', array('in' => $this->getProductDesignerValue()))
                    ->setPageSize(10);

                //$prCollection->getSelect()->limit( 1 )
                foreach ($prCollection as $pr_coll) {
                    $location_id = $pr_coll->getProductLocation();
                    if ($location_id) {
                        break;
                    }
                }

                if (!$location_id) {
                    return '-';
                }
            }

            $collection = Mage::getResourceModel('sales/order_item_collection');


            $product_location = Mage::getResourceSingleton('catalog/product')->getAttribute('product_location');

            $order_grand_total = Mage::getResourceSingleton('sales/order')->getAttribute('base_grand_total');

            // joining order info
            $collection->getSelect()
                ->joinLeft(array('table_order' => 'sales_order'),
                    'table_order.entity_id=main_table.order_id',
                    array('order_increment_id' => 'table_order.increment_id', 'order_base_total_paid' => 'table_order.base_total_paid')
                )

                ->joinLeft(array('table_order_grand_total' => $order_grand_total->getBackend()->getTable()),
                    'table_order_grand_total.entity_id=main_table.order_id ',
                    array('order_grand_total' => 'base_grand_total')
                )

                ->joinLeft(array('table_product_location_value' => $product_location->getBackend()->getTable()),
                    'table_product_location_value.entity_id=main_table.product_id and table_product_location_value.attribute_id=' . (int)$product_location->getAttributeId(),
                    array('product_location_value' => 'value')
                );

            $collection->addFieldToFilter('parent_item_id', array('null' => 1));
            $collection->addFieldToFilter('table_product_location_value.value', array('eq' => $location_id));

            $collection->getSelect()->where('table_order.base_total_paid > 0');

            $collection->getSelect()->group('table_product_location_value.value');
            $collection->getSelect()->columns("sum(base_row_total) as row_total");

            if ($collection->getSize() > 0) {
                $firstItem = $collection->getFirstItem();
                if ($firstItem->getBaseRowTotal() != 0) {
                    return $this->getBaseRowTotal() / $firstItem->getBaseRowTotal();
                } else {
                    return 0;
                }
            } else {
                return '-';
            }

        }

        public function getProdOptions()
        {
            return $this->getProductOptions();
        }

        public function getSimpleProductName()
        {
            $prodOption = $this->getProdOptions();
            if (isset($prodOption['simple_name']) && !empty($prodOption['simple_name'])) {
                return $prodOption['simple_name'];
            } else {
                return $this->getName();
            }
        }

        public function getSimpleProductId()
        {
            $prodOption = $this->getProdOptions();

            if (isset($prodOption['simple_sku']) && !empty($prodOption['simple_sku'])) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $prodOption['simple_sku']);
                if ($product && $prodOption['simple_sku'] == $product->getSku()) {
                    return $product->getId();
                } else {
                    return $this->getProductId();
                }
            } else {
                return $this->getProductId();
            }

        }

        public function getSimpleProductSku()
        {
            $prodOption = $this->getProdOptions();
            if (isset($prodOption['simple_sku']) && !empty($prodOption['simple_sku'])) {
                return $prodOption['simple_sku'];
            } else {
                return $this->getSku();
            }
        }

        public function getTotalRetailValue()
        {
            $retailValue = 0;
            foreach ($this->getCollectionProductByDesigner() as $product) {
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $retailValue += $stock->getQty() * $product->getPrice();
            }

            return number_format(floatval($retailValue), 2, '.', '');
        }

        /** Returns the number of products by designer
         * @return int
         *
         */
        public function getAllProductByDesigner()
        {
                return $this->getCollectionProductByDesigner()->getSize();
        }

        public function getCollectionProductByDesigner($designerId = null, $filter_field = null, $filter_value = null)
        {
            if (!$designerId) {
                $designerId = $this->getProductDesignerValue();
            }

            if (!$this->_collectionProductByDesigner) {
                $this->_collectionProductByDesigner = Mage::getResourceModel('catalog/product_collection')
                    ->addAttributeToSelect('*')
//                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('product_trend')
                    ->addAttributeToFilter('product_designer', array('in' => $designerId))
                    ->addAttributeToFilter('type_id', 'simple')
					->joinField(
						'qty',
						'cataloginventory/stock_item',
						'qty',
						'product_id=entity_id',
						'{{table}}.stock_id=1',
						'left'
				) ;
				
            }

            if (isset($filter_field) && isset($filter_value)){
                $this->_collectionProductByDesigner->addAttributeToFilter($filter_field, $filter_value);
            }
			
            return $this->_collectionProductByDesigner;
        }

        public function getProductCollection($filter, $predicate, $value)
        {
            if (!$this->_productCollection) {
                $this->_productCollection = Mage::getResourceModel('catalog/product_collection')
                    ->addAttributeToSelect('*')
                    ->addAttributeToSelect('product_trend')
                    ->addAttributeToFilter($filter, array($predicate => $value))
                    ->addAttributeToFilter('type_id', 'simple');
            }

            return $this->_productCollection;
        }

        public function getProductCollectionByLocation()
        {
            $this->getProductCollectionByLocation('product_location', 'eq', $this->getProductLocationValue());
        }

        public function getTotalRetailValueTest()
        {

            $this->getProductCollectionByLocation();
            $retailValue = 0;
            foreach ($this->_productCollection() as $product) {
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $retailValue += $stock->getQty() * $product->getPrice();
            }

            return number_format(floatval($retailValue), 2, '.', '');
        }

        public function setResetCollectionProductByDesigner()
        {
            $this->_collectionProductByDesigner = null;
        }

        public function getEmptyValue()
        {
            return '0.00';
        }

        public function getSeason()
        {
            return $this->getProductSeason();
        }

        /**
         * @return mixed
         */
        public function getItemSize()
        {
            // Getting additional info
            $options = $this->getProductOptions();
            $attributes_info = (isset($options['attributes_info']) && is_array($options['attributes_info']) ) ? $options['attributes_info'] : array();

            $result = '';

            foreach($attributes_info as $_attr) {
                if ($_attr['label'] != 'Size') {
                    continue;
                }
                $result = $_attr['value'];
                break;
            }

            return $result;
        }

        public function getTrend()
        {
            return $this->getData('product_trend');
        }

        public function getProductLocationReport()
        {
            return $this->getData('product_location');
        }

        public function getProductConsigmentOrder()
        {
            return $this->getData('product_consigment');
        }

        public function getStock()
        {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
            $stock   = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

            return $stock;
        }

        public function getStockQty()
        {
            if (empty($this->_currentQty)){
                $this->_currentQty = (int)$this->getStock()->getQty();
            }

            return $this->_currentQty;
        }

        /**
         * Total Current Inventory
         *
         * Using in - TOTAL PRODUCT SALES
         *
         * @return string
         */
        public function getCurrentInventory()
        {

            $totalQty = 0;
            foreach ($this->getCollectionProductByDesigner() as $product) {
                //$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $totalQty += (int)$product->getQty();
            }
		
            return $totalQty;
        }

        public function getInventory()
        {
            return  number_format(floatval($this->getStockQty()) * floatval($this->getPrice()), 2, '.', '');
        }

        public function getInventoryAllProduct()
        {
            $totalPrice = 0.00;
            foreach($this->getCollectionProductByDesigner() as $product){
                $qty   = floatval(Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty());
                $price = floatval($product->getFinalPrice());
                $totalPrice += ($qty * $price);
            }

            return  number_format($totalPrice, 2, '.', '');
        }

        public function getTotalSalesCs()
        {
            //$total = $this->getMarkdownSales() + $this->getRegularSales() - $this->getDiscountOnSale();
			$total = $this->getMarkdownSales() + $this->getRegularSales();
            return  number_format(round($total, 2), 2, '.', '');
        }

        /**
         * Category sales
         * @return string
         */
        public function getNetTotalSalesCs()
        {
            return number_format(round($this->getTotalSalesCs() - $this->getTotalRefunded(), 2), 2, '.', '');
        }

        public function getCostTotalCs()
        {
            return number_format(round($this->getCostTotal(), 2), 2, '.', '');
        }

        /**
         * Profit = Net Total Sales - Cost
         * @return string
         */
        public function getTotalProfitCs()
        {
            return number_format(round($this->getNetTotalSalesCs() - $this->getTotalCost(), 2), 2, '.', '');
        }
    }

