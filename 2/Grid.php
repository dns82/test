<?php

    class IB_Report_Block_Adminhtml_Orders_Grid extends IB_Report_Block_Adminhtml_Grid
    {
        //IB_Report_Block_Adminhtml_Grid
        //Mage_Adminhtml_Block_Report_Grid
        public function __construct()
        {
            parent::__construct();
            $this->setTemplate('ib/report/f_grid.phtml');
            $this->setId('gridOrdersCustomer');
        }

        protected function _prepareCollection()
        {

            $collection = Mage::getResourceModel('sales/order_item_collection');

            $filter = $this->getParam($this->getVarNameFilter(), null);

            if (is_string($filter)) {
                $data   = array();
                $filter = base64_decode($filter);
                parse_str(urldecode($filter), $data);
            }

            if (isset($data['designer']) && $data['designer'] != 0) {
                $str_tbl_pr_designer = 'table_product_designer.option_id=table_product_designer_value.value and table_product_designer.option_id = ' . $data['designer'];
            } else {
                $str_tbl_pr_designer = 'table_product_designer.option_id=table_product_designer_value.value';
            }

            // Loading attribute
            $shipping_address_id    = Mage::getResourceSingleton('sales/order')->getAttribute('shipping_address_id');
            $shipping_address_city  = Mage::getResourceSingleton('sales/order_address')->getAttribute('city');
            $shipping_address_state = Mage::getResourceSingleton('sales/order_address')->getAttribute('region');

            $order_grand_total = Mage::getResourceSingleton('sales/order')->getAttribute('base_grand_total');

            $customer_firstname = Mage::getResourceSingleton('sales/order')->getAttribute('customer_firstname');
            $customer_lastname  = Mage::getResourceSingleton('sales/order')->getAttribute('customer_lastname');

            $product_retailer               = Mage::getResourceSingleton('catalog/product')->getAttribute('retailer_id');
            $product_designer               = Mage::getResourceSingleton('catalog/product')->getAttribute('product_designer');
            $product_consigment             = Mage::getResourceSingleton('catalog/product')->getAttribute('product_consigment');
            $product_cost                   = Mage::getResourceSingleton('catalog/product')->getAttribute('cost');
            $product_orig_price             = Mage::getResourceSingleton('catalog/product')->getAttribute('price');
            $product_profit_formula         = Mage::getResourceSingleton('catalog/product')->getAttribute('product_profit_formula');
            $product_profit_formula_percent = Mage::getResourceSingleton('catalog/product')->getAttribute('product_profit_formula_percent');
            $product_fulfillment_charge     = Mage::getResourceSingleton('catalog/product')->getAttribute('product_fulfillment_charge');
            $product_trend                  = Mage::getResourceSingleton('catalog/product')->getAttribute('product_trend');
            $product_season                 = Mage::getResourceSingleton('catalog/product')->getAttribute('product_season');
            // joining order info
            $collection->getSelect()
                ->joinLeft(array('table_order' => 'sales_order'),
                    'table_order.entity_id=main_table.order_id',
                    array('order_increment_id' => 'table_order.increment_id', 'order_base_total_paid' => 'table_order.base_total_paid', 'order_customer_id' => 'table_order.customer_id')
                )

                ->joinLeft(array('table_order_created_at' => 'sales_order'),
                    'table_order_created_at.entity_id=main_table.order_id',
                    array('order_created_at' => 'table_order_created_at.created_at')
                )

                ->joinLeft(array('table_order_shipping_address_id' => $shipping_address_id->getBackend()->getTable()),
                    'table_order_shipping_address_id.entity_id=main_table.order_id and table_order_shipping_address_id.attribute_id=' . (int)$shipping_address_id->getAttributeId(),
                    array('shipping_address_id' => 'table_order_shipping_address_id.value')
                )

                ->joinLeft(array('table_order_shipping_address_city' => $shipping_address_city->getBackend()->getTable()),
                    'table_order_shipping_address_city.entity_id=table_order_shipping_address_id.value and table_order_shipping_address_city.attribute_id=' . (int)$shipping_address_city->getAttributeId(),
                    array('shipping_address_city' => 'table_order_shipping_address_city.value')
                )
                ->joinLeft(array('table_shipping_address_state' => $shipping_address_state->getBackend()->getTable()),
                    'table_shipping_address_state.entity_id=table_order_shipping_address_id.value and table_shipping_address_state.attribute_id=' . (int)$shipping_address_state->getAttributeId(),
                    array('shipping_address_state' => 'table_shipping_address_state.value')
                )

                ->joinInner(null, null, array('shipping_address' => 'CONCAT(table_order_shipping_address_city.value, ", ", table_shipping_address_state.value)'))

                ->joinLeft(array('table_customer_firstname' => $customer_firstname->getBackend()->getTable()),
                    'table_customer_firstname.entity_id=main_table.order_id and table_customer_firstname.attribute_id=' . (int)$customer_firstname->getAttributeId(),
                    array('customer_firstname' => 'value')
                )

                ->joinLeft(array('table_customer_lastname' => $customer_lastname->getBackend()->getTable()),
                    'table_customer_lastname.entity_id=main_table.order_id and table_customer_lastname.attribute_id=' . (int)$customer_lastname->getAttributeId(),
                    array('customer_lastname' => 'value')
                )

                ->joinLeft(array('table_order_grand_total' => $order_grand_total->getBackend()->getTable()),
                    'table_order_grand_total.entity_id=main_table.order_id ',
                    array('order_grand_total' => 'base_grand_total')
                )

                ->joinInner(null, null, array('customer_name' => 'CONCAT(table_customer_firstname.value, " ", table_customer_lastname.value)'))

                ->joinLeft(array('table_product_retailer_value' => $product_retailer->getBackend()->getTable()),
                    'table_product_retailer_value.entity_id=main_table.product_id and table_product_retailer_value.attribute_id=' . (int)$product_retailer->getAttributeId(),
                    array('product_retailer_id' => 'value')
                )

                ->joinInner(array('table_product_retailer' => 'admin_user'),
                    'table_product_retailer.user_id= table_product_retailer_value.value',
                    array('pr_ret_id' => 'user_id')
                )

                ->joinLeft(array('table_product_designer_value' => $product_designer->getBackend()->getTable()),
                    'table_product_designer_value.entity_id=main_table.product_id and table_product_designer_value.attribute_id=' . (int)$product_designer->getAttributeId(),
                    array('product_designer_value' => 'value')
                )

                ->joinLeft(array('table_product_designer' => 'eav_attribute_option_value'),
                    $str_tbl_pr_designer,
                    array('product_designer' => 'value')
                )

                ->joinLeft(array('table_product_trend_value' => $product_trend->getBackend()->getTable()),
                    'table_product_trend_value.entity_id=main_table.product_id and table_product_trend_value.attribute_id=' . (int)$product_trend->getAttributeId(),
                    array('product_trend_value' => 'value')
                )
                ->joinLeft(array('table_product_trend' => 'eav_attribute_option_value'),
                    'table_product_trend.option_id=table_product_trend_value.value',
                    array('product_trend' => 'value')
                )

                ->joinLeft(array('table_product_season_value' => $product_season->getBackend()->getTable()),
                    'table_product_season_value.entity_id=main_table.product_id and table_product_season_value.attribute_id=' . (int)$product_season->getAttributeId(),
                    array('product_season_value' => 'value')
                )
                ->joinLeft(array('table_product_season' => 'eav_attribute_option_value'),
                    'table_product_season.option_id=table_product_season_value.value',
                    array('product_season' => 'value')
                )


                ->joinLeft(array('table_product_consigment_value' => $product_consigment->getBackend()->getTable()),
                    'table_product_consigment_value.entity_id=main_table.product_id and table_product_consigment_value.attribute_id=' . (int)$product_consigment->getAttributeId(),
                    array('product_consigment_value' => 'value')
                )
                ->joinInner(array('table_product_consigment' => 'eav_attribute_option_value'),
                    'table_product_consigment.option_id=table_product_consigment_value.value',
                    array('product_consigment' => 'value')
                )

                ->joinLeft(array('table_product_cost_value' => $product_cost->getBackend()->getTable()),
                    'table_product_cost_value.entity_id=main_table.product_id and table_product_cost_value.attribute_id=' . (int)$product_cost->getAttributeId(),
                    array('product_cost_value' => 'value')
                )

                ->joinLeft(array('table_product_orig_price_value' => $product_orig_price->getBackend()->getTable()),
                    'table_product_orig_price_value.entity_id=main_table.product_id and table_product_orig_price_value.attribute_id=' . (int)$product_orig_price->getAttributeId(),
                    array('product_orig_price' => 'value')
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


                ->joinInner(null, null, array('order_shipping_amount' => 'table_order.base_shipping_amount'))
                ->joinInner(null, null, array('order_tax_amount' => 'table_order.base_tax_amount'))

                //->joinInner( null, null, new Zend_Db_Expr('main_table.row_total - main_table.discount_amount as net_total'))

                //->joinInner( null, null, new Zend_Db_Expr('main_table.cost * main_table.qty_ordered as cost_total'))
                //->joinInner( null, null, new Zend_Db_Expr('main_table.row_total - cost_total - '))
                //array('net_total'=>'main_table.row_total - main_table.discount_amount')
            ;


            $collection->addFieldToFilter('parent_item_id', array('null' => 1));
            $collection->getSelect()->where('table_order.base_total_paid > 0');

            //$collection->addFieldToFilter( 'product_designer', 'Bensoni' );

            /*$coll = Mage::getModel('catalog/product')->getCollection()
             ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
            foreach($collection as $col){
                print_r($col);
            }
            exit;*/

            //echo($collection->getSelect()->__toString());

            $this->setCollection($collection);
            parent::_prepareCollection();

        }


        protected function _afterLoadCollection()
        {
            //Mage::log($this->getCollection()->getSelect()->__toString());

            parent::_afterLoadCollection();
            //var_dump($this->getCollection()->getSelect()->__toString());die;
        }

        protected function _getStore()
        {
            $storeId = (int)$this->getRequest()->getParam('store', 0);

            return Mage::app()->getStore($storeId);
        }


        protected function _prepareColumns()
        {

            $store = $this->_getStore();

            $this->addColumn('order_created_at', array(
                'header'   => $this->__('Purchased On'),
                'sortable' => false,
                'index'    => 'order_created_at',
                'type'     => 'datetime',
                //'format'	=>	Varien_Date::DATE_INTERNAL_FORMAT,
            ));

            $this->addColumn('order_increment_id', array(
                'header'   => $this->__('Order #'),
                'sortable' => false,
                'index'    => 'order_increment_id'
            ));

            $this->addColumn('order_customer_id', array(
                'header'   => $this->__('Customer #'),
                'sortable' => false,
                'index'    => 'order_customer_id',
                'align'    => 'right'
            ));

            $this->addColumn('customer_name', array(
                'header'   => $this->__('Customer name'),
                'sortable' => false,
                'index'    => 'customer_name',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_perorder',
            ));

            $this->addColumn('shipping_address', array(
                'header'   => $this->__('Shipping Address'),
                'sortable' => false,
                'index'    => 'shipping_address',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_perorder',
            ));

            $this->addColumn('product_retailer', array(
                'header'   => $this->__('Retailer'),
                'sortable' => false,
                'getter'   => 'getRetailName',
                'width'    => '200px'
            ));

            $this->addColumn('product_designer', array(
                'header'   => $this->__('Designer'),
                'sortable' => false,
                'index'    => 'product_designer'
            ));

            $this->addColumn('product_trend', array(
                'header'   => $this->__('Trend'),
                'sortable' => false,
                'index'    => 'product_trend'
            ));

            $this->addColumn('product_season', array(
                'header'   => $this->__('Season'),
                'sortable' => false,
                'index'    => 'product_season'
            ));

            $this->addColumn('product_consigment', array(
                'header'   => $this->__('INV Type'),
                'sortable' => false,
                'index'    => 'product_consigment'
            ));

            $this->addColumn('size', array(
                'header'   => $this->__('Size'),
                'sortable' => false,
                'index'    => 'size',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_size'
            ));

            $this->addColumn('qty_ordered', array(
                'header'   => $this->__('Units Sold'),
                'type'     => 'number',
                'width'    => '20px',
                'sortable' => false,
                'index'    => 'qty_ordered',
            ));

            $this->addColumn('product_id', array(
                'header'   => $this->__('Product Id'),
                'sortable' => false,
                //'index'     => 'product_id',
                'align'    => 'right',
                'getter'   => 'getSimpleProductId'
            ));

            $this->addColumn('sku', array(
                'header'   => $this->__('SKU #'),
                'sortable' => false,
                //'index'     => 'sku'
                'getter'   => 'getSimpleProductSku'
            ));

            $this->addColumn('name', array(
                'header'   => $this->__('Item name'),
                'sortable' => false,
                //'index'     => 'name'
                'getter'   => 'getSimpleProductName'
            ));

            $this->addColumn('top_type', array(
               'header'   => $this->__('Item type (Top Category)'),
                'sortable' => false,
                'getter'   => 'getTopCategoryLabel'
            ));

           /* $this->addColumn('type', array(
                'header'   => $this->__('Item type (Sub-Category)'),
                'sortable' => false,
                'getter'   => 'getLowestCategoryLabel'
            ));*/

            $this->addColumn('price', array(
                'header'   => $this->__('Retail price'),
                'sortable' => false,
                'getter'   => 'getRetailPriceInPurchase',
//			'align'		=> 'right',
                //'type'  => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('special_price', array(
                'header'   => $this->__('Markdown price'),
                'sortable' => false,
                'getter'   => 'getSpecialPriceInPurchase',
                'align'    => 'right',
//			'index'		=> 'special_price'
                //'type'  => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));


            $this->addColumn('total_reg_sales', array(
                'header'   => $this->__('Total Regular Sales'),
                'sortable' => false,
                'getter'   => 'getTotalRegularSalesCOR',
                'align'    => 'right'
            ));

            $this->addColumn('total_markdown_sales', array(
                'header'   => $this->__('Total Markdown Sales'),
                'sortable' => false,
                'getter'   => 'getTotalMarkdownSales',
                'align'    => 'right'
            ));

            $this->addColumn('discount_amount', array(
                'header'    => $this->__('Total Discount to Sale'),
                'sortable'  => false,
                'getter'     => 'getDiscountAmountPromoCode',
//                'index'     => 'discount_amount'
                'align'    => 'right'
            ));

            $this->addColumn('total_sales', array(
                'header'    => $this->__('Total Sales'),
                'sortable'  => false,
                'getter'     => 'getTotalAmountSalesCOR'
            ));

            $this->addColumn('refund_amount', array(
                'header'   => $this->__('Refunded'),
                'sortable' => false,
                'getter'   => 'getRefundAmountCOR',
                'align'    => 'right',
            ));

            $this->addColumn('net_total_sales', array(
                'header'   => $this->__('Net Total Sales'),
                'sortable' => false,
                'getter'   => 'getNetTotalAmountSalesCOR',
                'align'    => 'right',
            ));

            $this->addColumn('order_shipping_amount', array(
                'header'   => $this->__('Shipping Charge'),
                'sortable' => false,
                'index'    => 'order_shipping_amount',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_perorder',
                'align'    => 'right',
                'type'     => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('order_tax_amount', array(
                'header'   => $this->__('Taxes'),
                'sortable' => false,
                'index'    => 'order_tax_amount',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_perorder',
                'align'    => 'right',
                'type'     => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('order_grand_total', array(
                'header'   => $this->__('Gross Total Sales'),
                'sortable' => false,
                'getter'		=> 'getTotalGrossTotalSalesCOR',
//                'index'    => 'order_grand_total',
//                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_perorder',
//                'type'     => 'price',
                'align'    => 'right',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('product_cost_value', array(
                'header'   => $this->__('Cost'),
                'sortable' => false,
                //'getter'     => 'getCostTotal',
                'index'    => 'product_cost_value',
                'align'    => 'right',
                'renderer' => 'ibreport/adminhtml_report_grid_column_renderer_price',
                'type'     => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('product_cost_c', array(
                'header'   => $this->__('Total Cost'),
                'sortable' => false,
                'getter'   => 'getCostTotal',
                'align'    => 'right',
                //'type'  => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('fulfillment_charge', array(
                'header'   => $this->__('Fulfillment Charge'),
                'sortable' => false,
                'getter'   => 'getFulfChargeCOR',
                'align'    => 'right',
                //'type'  => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            $this->addColumn('profit', array(
                'header'   => $this->__('Profit'),
                'sortable' => false,
                //'index'     => 'profit',
                'getter'   => 'getClearProfitCOR',
                'align'    => 'right',
                //'type'  => 'price',
                //'currency_code' => $store->getBaseCurrency()->getCode(),
            ));

            //$this->addExportType('*/*/exportOrdersCsv', Mage::helper('reports')->__('CSV'));
            $this->addExportType('*/*/exportOrdersExcel', Mage::helper('reports')->__('Excel'));

            return parent::_prepareColumns();
        }

    }