<?php

namespace Grocy\Controllers;

use Grocy\Helpers\Grocycode;
use Grocy\Services\RecipesService;
use Grocy\Services\ExcelService;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StockController extends BaseController
{
	use GrocycodeTrait;

	public function __construct(Container $container)
	{
		parent::__construct($container);

		try {
			$externalBarcodeLookupPluginName = $this->getStockService()->GetExternalBarcodeLookupPluginName();
		} catch (\Exception $e) {
			$externalBarcodeLookupPluginName = '';
		} finally {
			$this->View->set('ExternalBarcodeLookupPluginName', $externalBarcodeLookupPluginName);
		}
	}

	public function Consume(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'consume', [
			'products' => $this->getDatabase()->products()->where('active = 1')->where('id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)')->orderBy('name'),
			'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
			'recipes' => $this->getDatabase()->recipes()->where('type', RecipesService::RECIPE_TYPE_NORMAL)->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved()
		]);
	}

	public function Inventory(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'inventory', [
			'products' => $this->getDatabase()->products()->where('active = 1 AND no_own_stock = 0')->orderBy('name', 'COLLATE NOCASE'),
			'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
			'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
			'userfields' => $this->getUserfieldsService()->GetFields('stock')
		]);
	}

	public function Journal(Request $request, Response $response, array $args)
	{
		if (isset($request->getQueryParams()['months']) && filter_var($request->getQueryParams()['months'], FILTER_VALIDATE_INT) !== false) {
			$months = $request->getQueryParams()['months'];
			$where = "row_created_timestamp > DATE(DATE('now', 'localtime'), '-$months months')";
		} else {
			// Default 6 months
			$where = "row_created_timestamp > DATE(DATE('now', 'localtime'), '-6 months')";
		}

		if (isset($request->getQueryParams()['product']) && filter_var($request->getQueryParams()['product'], FILTER_VALIDATE_INT) !== false) {
			$productId = $request->getQueryParams()['product'];
			$where .= " AND product_id = $productId";
		}

		$usersService = $this->getUsersService();

		return $this->renderPage($response, 'stockjournal', [
			'stockLog' => $this->getDatabase()->uihelper_stock_journal()->where($where)->orderBy('row_created_timestamp', 'DESC'),
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
			'users' => $usersService->GetUsersAsDto(),
			'transactionTypes' => GetClassConstants('\Grocy\Services\StockService', 'TRANSACTION_TYPE_'),
			'userfieldsStock' => $this->getUserfieldsService()->GetFields('stock'),
			'userfieldValuesStock' => $this->getUserfieldsService()->GetAllValues('stock')
		]);
	}

	public function LocationContentSheet(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'locationcontentsheet', [
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
			'currentStockLocationContent' => $this->getStockService()->GetCurrentStockLocationContent(isset($request->getQueryParams()['include_out_of_stock']))
		]);
	}

	public function LocationEditForm(Request $request, Response $response, array $args)
	{
		if ($args['locationId'] == 'new') {
			return $this->renderPage($response, 'locationform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('locations')
			]);
		} else {
			return $this->renderPage($response, 'locationform', [
				'location' => $this->getDatabase()->locations($args['locationId']),
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('locations')
			]);
		}
	}

	public function LocationsList(Request $request, Response $response, array $args)
	{
		if (isset($request->getQueryParams()['include_disabled'])) {
			$locations = $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE');
		} else {
			$locations = $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
		}

		return $this->renderPage($response, 'locations', [
			'locations' => $locations,
			'userfields' => $this->getUserfieldsService()->GetFields('locations'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('locations')
		]);
	}

	public function Overview(Request $request, Response $response, array $args)
	{
		$usersService = $this->getUsersService();
		$userSettings = $usersService->GetUserSettings(GROCY_USER_ID);
		$nextXDays = $userSettings['stock_due_soon_days'];

		$where = 'is_in_stock_or_below_min_stock = 1';
		if (boolval($userSettings['stock_overview_show_all_out_of_stock_products'])) {
			$where = 'p.hide_on_stock_overview = 0';
		}

		//获取分页参数
		$limit = $request->getQueryParams()['page_size'] ?? 20;
		$page = $request->getQueryParams()['page'] ?? 1;
		$offset = ($page - 1) * $limit;
		//获取搜索参数
		$search = $request->getQueryParams()['search'] ?? '';
		if (!empty($search)) {
			$where .= " AND (`product_name` like '%$search%' OR ufv.value like '%$search%')";
		}

		//获取位置参数
		$locationId = $request->getQueryParams()['locatoin_id'] ?? '';

		if (!empty($locationId)) {
			$where .= " AND (`product_location_id` = '$locationId')";
		}

		//获取产品组
		$productGroupId = $request->getQueryParams()['group_id'] ?? '';
		if (!empty($productGroupId)) {
			$where .= " AND (`group_id` = '$productGroupId')";
		}

		//获取状态标签
		$status = $request->getQueryParams()['status'] ?? '';
		if (!empty($status)) {
			switch ($status) {
				case 'instockX':
					$where .= " AND (`amount_aggregated` > 0)";
					break;
				case 'belowminstockamount':
					$where .= " AND (`product_missing` > 0)";
					break;
			}
		}

		$sql = "SELECT DISTINCT 
	p.id,
	sc.amount_opened AS amount_opened,
	p.tare_weight AS tare_weight,
	p.enable_tare_weight_handling AS enable_tare_weight_handling,
	sc.amount AS amount,
	sc.value as value,
	sc.product_id AS product_id,
	IFNULL(sc.best_before_date, '2888-12-31') AS best_before_date,
	EXISTS(SELECT id FROM stock_missing_products WHERE id = sc.product_id) AS product_missing,
	p.name AS product_name,
	p.location_id AS product_location_id,
	pg.name AS product_group_name,
	pg.id AS group_id,
	EXISTS(SELECT * FROM shopping_list WHERE shopping_list.product_id = sc.product_id) AS on_shopping_list,
	qu_stock.name AS qu_stock_name,
	qu_stock.name_plural AS qu_stock_name_plural,
	qu_purchase.name AS qu_purchase_name,
	qu_purchase.name_plural AS qu_purchase_name_plural,
	qu_consume.name AS qu_consume_name,
	qu_consume.name_plural AS qu_consume_name_plural,
	qu_price.name AS qu_price_name,
	qu_price.name_plural AS qu_price_name_plural,
	sc.is_aggregated_amount,
	sc.amount_opened_aggregated,
	sc.amount_aggregated,
	p.calories AS product_calories,
	sc.amount * p.calories AS calories,
	sc.amount_aggregated * p.calories AS calories_aggregated,
	p.quick_consume_amount,
	p.quick_consume_amount / p.qu_factor_consume_to_stock AS quick_consume_amount_qu_consume,
	p.quick_open_amount,
	p.quick_open_amount / p.qu_factor_consume_to_stock AS quick_open_amount_qu_consume,
	p.due_type,
	plp.purchased_date AS last_purchased,
	plp.price AS last_price,
	pap.price as average_price,
	p.min_stock_amount,
	pbcs.barcodes AS product_barcodes,
	p.description AS product_description,
	l.name AS product_default_location_name,
	p_parent.id AS parent_product_id,
	p_parent.name AS parent_product_name,
	p.picture_file_name AS product_picture_file_name,
	p.no_own_stock AS product_no_own_stock,
	p.qu_factor_purchase_to_stock AS product_qu_factor_purchase_to_stock,
	p.qu_factor_price_to_stock AS product_qu_factor_price_to_stock,
	sc.is_in_stock_or_below_min_stock,
	p.disable_open
FROM (
	SELECT *, 1 AS is_in_stock_or_below_min_stock
	FROM stock_current
	WHERE best_before_date IS NOT NULL
	UNION
	SELECT m.id, 0, 0, 0, null, 0, 0, 0, p.due_type, 1 AS is_in_stock_or_below_min_stock
	FROM stock_missing_products m
	JOIN products p
		ON m.id = p.id
	WHERE m.id NOT IN (SELECT product_id FROM stock_current)
	UNION
	SELECT p2.id, 0, 0, 0, null, 0, 0, 0, p2.due_type, 0 AS is_in_stock_or_below_min_stock
	FROM products p2
	WHERE active = 1
		AND p2.id NOT IN (SELECT product_id FROM stock_current UNION SELECT id FROM stock_missing_products)
	) sc
JOIN products_view p
    ON sc.product_id = p.id
JOIN locations l
	ON p.location_id = l.id
JOIN quantity_units qu_stock
	ON p.qu_id_stock = qu_stock.id
JOIN quantity_units qu_purchase
	ON p.qu_id_purchase = qu_purchase.id
JOIN quantity_units qu_consume
	ON p.qu_id_consume = qu_consume.id
JOIN quantity_units qu_price
	ON p.qu_id_price = qu_price.id
LEFT JOIN product_groups pg
	ON p.product_group_id = pg.id
LEFT JOIN cache__products_last_purchased plp
	ON sc.product_id = plp.product_id
LEFT JOIN cache__products_average_price pap
	ON sc.product_id = pap.product_id
LEFT JOIN product_barcodes_comma_separated pbcs
	ON sc.product_id = pbcs.product_id
LEFT JOIN products p_parent
	ON p.parent_product_id = p_parent.id
LEFT JOIN userfield_values ufv
    ON p.id = ufv.object_id
WHERE {$where}";
		$totalRes = $this->getDatabase()->query('select count(*) as total from (' . $sql . ')')->fetchAll(\PDO::FETCH_OBJ);
		$sql .=  " LIMIT {$limit} OFFSET {$offset};";
		$currentStock = $this->getDatabase()->query($sql)->fetchAll(\PDO::FETCH_OBJ);
		//生成分页
		$pagination = new \Grocy\Helpers\Pagination($totalRes[0]->total,$limit);
  
		return $this->renderPage($response, 'stockoverview', [
			'currentStock' => $currentStock,
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'currentStockLocations' => $this->getStockService()->GetCurrentStockLocations(),
			'nextXDays' => $nextXDays,
			'total' => $totalRes[0]->total,
			'currentPage' => $page,
			'pageSize' => $limit,
			'pagination' => $pagination->render(),
			'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'userfields' => $this->getUserfieldsService()->GetFields('products'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('products')
		]);
	}

	public function ProductBarcodesEditForm(Request $request, Response $response, array $args)
	{
		$product = null;
		if (isset($request->getQueryParams()['product'])) {
			$product = $this->getDatabase()->products($request->getQueryParams()['product']);
		}

		if ($args['productBarcodeId'] == 'new') {
			return $this->renderPage($response, 'productbarcodeform', [
				'mode' => 'create',
				'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
				'product' => $product,
				'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
				'userfields' => $this->getUserfieldsService()->GetFields('product_barcodes')
			]);
		} else {
			return $this->renderPage($response, 'productbarcodeform', [
				'mode' => 'edit',
				'barcode' => $this->getDatabase()->product_barcodes($args['productBarcodeId']),
				'product' => $product,
				'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
				'userfields' => $this->getUserfieldsService()->GetFields('product_barcodes')
			]);
		}
	}

	public function ProductEditForm(Request $request, Response $response, array $args)
	{
		if ($args['productId'] == 'new') {
			return $this->renderPage($response, 'productform', [
				'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name'),
				'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
				'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityunitsStock' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
				'referencedQuantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'productgroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'userfields' => $this->getUserfieldsService()->GetFields('products'),
				'products' => $this->getDatabase()->products()->where('parent_product_id IS NULL and active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'isSubProductOfOthers' => false,
				'mode' => 'create'
			]);
		} else {
			$product = $this->getDatabase()->products($args['productId']);

			return $this->renderPage($response, 'productform', [
				'product' => $product,
				'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'barcodes' => $this->getDatabase()->product_barcodes()->orderBy('barcode'),
				'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityunitsStock' => $this->getDatabase()->quantity_units()->where('id IN (SELECT to_qu_id FROM cache__quantity_unit_conversions_resolved WHERE product_id = :1) OR NOT EXISTS(SELECT 1 FROM stock_log WHERE product_id = :1)', $product->id)->orderBy('name', 'COLLATE NOCASE'),
				// 'referencedQuantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->where('id IN (SELECT to_qu_id FROM cache__quantity_unit_conversions_resolved WHERE product_id = :1)', $product->id)->orderBy('name', 'COLLATE NOCASE'),
				'referencedQuantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'productgroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'userfields' => $this->getUserfieldsService()->GetFields('products'),
				'products' => $this->getDatabase()->products()->where('id != :1 AND parent_product_id IS NULL and active = 1', $product->id)->orderBy('name', 'COLLATE NOCASE'),
				'isSubProductOfOthers' => $this->getDatabase()->products()->where('parent_product_id = :1', $product->id)->count() !== 0,
				'mode' => 'edit',
				'quConversions' => $this->getDatabase()->quantity_unit_conversions()->where('product_id', $product->id),
				'productBarcodeUserfields' => $this->getUserfieldsService()->GetFields('product_barcodes'),
				'productBarcodeUserfieldValues' => $this->getUserfieldsService()->GetAllValues('product_barcodes')
			]);
		}
	}

	public function ProductGrocycodeImage(Request $request, Response $response, array $args)
	{
		$gc = new Grocycode(Grocycode::PRODUCT, $args['productId']);
		return $this->ServeGrocycodeImage($request, $response, $gc);
	}

	public function ProductGroupEditForm(Request $request, Response $response, array $args)
	{
		if ($args['productGroupId'] == 'new') {
			return $this->renderPage($response, 'productgroupform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('product_groups')
			]);
		} else {
			return $this->renderPage($response, 'productgroupform', [
				'group' => $this->getDatabase()->product_groups($args['productGroupId']),
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('product_groups')
			]);
		}
	}

	public function ProductGroupsList(Request $request, Response $response, array $args)
	{
		if (isset($request->getQueryParams()['include_disabled'])) {
			$productGroups = $this->getDatabase()->product_groups()->orderBy('name', 'COLLATE NOCASE');
		} else {
			$productGroups = $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
		}

		return $this->renderPage($response, 'productgroups', [
			'productGroups' => $productGroups,
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'userfields' => $this->getUserfieldsService()->GetFields('product_groups'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('product_groups')
		]);
	}

	public function ProductsList(Request $request, Response $response, array $args)
	{
		$products = $this->getDatabase()->products();
		if (!isset($request->getQueryParams()['include_disabled'])) {
			$products = $products->where('active = 1');
		}

		if (isset($request->getQueryParams()['only_in_stock'])) {
			$products = $products->where('id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)');
		}
		if (isset($request->getQueryParams()['only_out_of_stock'])) {
			$products = $products->where('id NOT IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)');
		}
        if(isset($request->getQueryParams()['group_id'])){
            $products = $products->where('group_id = ?', $request->getQueryParams()['group_id']);
        }
        if(isset($request->getQueryParams()['search'])){
        	$products = $products->where('name like ?', '%'. $request->getQueryParams()['search']. '%');
        }
		$page = $request->getQueryParams()['page'] ?? 1;
		$limit = $request->getQueryParams()['page_size'] ?? 20;
		$offset = ($page - 1) * $limit;

		$pagination = new \Grocy\Helpers\Pagination($products->count(), $limit);
		$products = $products->limit($limit, $offset);
		$products = $products->orderBy('name', 'COLLATE NOCASE');

		return $this->renderPage($response, 'products', [
			'products' => $products,
			'pagination' => $pagination->render(),
			'locations' => $this->getDatabase()->locations()->orderBy('name', 'COLLATE NOCASE'),
			'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
			'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'shoppingLocations' => $this->getDatabase()->shopping_locations()->orderBy('name', 'COLLATE NOCASE'),
			'userfields' => $this->getUserfieldsService()->GetFields('products'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('products')
		]);
	}

	public function ExportProductsList(Request $request, Response $response, array $args)
	{
		$param = $request->getQueryParams();

		$where = [];
		if ($param['group_name'] != 'all') {
			$where[] = 'product_groups.name like "%' . $param['group_name'] . '%"';
		}
		if ($param['status'] != 'all') {
			if ($param['status'] == 'in') {
				$where[] = 'products.id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)';
			} else {
				$where[] = 'products.id NOT IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)';
			}
		}

		if ($param['showDisabled'] == '1') {
			$where[] = 'products.active = 1';
		}

		$whereStr = '1';
		if ($where) {
			$whereStr = '(' . implode(' AND ', $where) . ')';
		}

		$sql = 'SELECT products.id,products.name FROM products LEFT JOIN product_groups ON products.product_group_id = product_groups.id WHERE ' . $whereStr . ' ORDER BY products.id desc';
		$list = $this->getDatabaseService()->ExecuteDbQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
		$dataList = [];
		if ($list) {
			// 查找扩展字段
			$sql = 'select id,name,caption from userfields where entity="products" order by id asc';
			$fieldList = $this->getDatabaseService()->ExecuteDbQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
			$fieldIdArr = [];
			$fieldMap = [];
			foreach ($fieldList as $v) {
				$fieldIdArr[] = $v['id'];
				$fieldMap[$v['id']] = $v;
			}
			// 查找扩展表数据
			$sql = 'select object_id,field_id,value from userfield_values where field_id in (' . implode(',', $fieldIdArr) . ') order by object_id asc';
			$fieldValList = $this->getDatabaseService()->ExecuteDbQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);
			$extFieldValMap = [];
			foreach ($fieldValList as $v) {
				if (!isset($extFieldValMap[$v['object_id']])) {
					$extFieldValMap[$v['object_id']] = [];
				}
				$extFieldValMap[$v['object_id']][$fieldMap[$v['field_id']]['name']] = $v['value'];
			}

			foreach ($list as $v) {
				$v['product_name'] = $v['name'];
				$v['product_id'] = $v['id'];
				if (isset($extFieldValMap[$v['id']])) {
					$v = array_merge($v, $extFieldValMap[$v['id']]);
				}

				foreach ($fieldMap as $field) {
					if (!isset($v[$field['name']])) {
						$v[$field['name']] = '';
					}
				}
				unset($v['name'], $v['id']);
				$dataList[] = $v;
			}
		}

		ExcelService::exportToExcel($dataList, 'product-' . date('Ymd') . '.xlsx');
	}

	public function Purchase(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'purchase', [
			'products' => $this->getDatabase()->products()->where('active = 1 AND no_own_stock = 0')->orderBy('name', 'COLLATE NOCASE'),
			'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
			'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
			'userfields' => $this->getUserfieldsService()->GetFields('stock')
		]);
	}

	public function QuantityUnitConversionEditForm(Request $request, Response $response, array $args)
	{
		$product = null;
		if (isset($request->getQueryParams()['product'])) {
			$product = $this->getDatabase()->products($request->getQueryParams()['product']);
		}

		$defaultQuUnit = null;

		if (isset($request->getQueryParams()['qu-unit'])) {
			$defaultQuUnit = $this->getDatabase()->quantity_units($request->getQueryParams()['qu-unit']);
		}

		if ($args['quConversionId'] == 'new') {
			return $this->renderPage($response, 'quantityunitconversionform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('quantity_unit_conversions'),
				'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'product' => $product,
				'defaultQuUnit' => $defaultQuUnit
			]);
		} else {
			return $this->renderPage($response, 'quantityunitconversionform', [
				'quConversion' => $this->getDatabase()->quantity_unit_conversions($args['quConversionId']),
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('quantity_unit_conversions'),
				'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'product' => $product,
				'defaultQuUnit' => $defaultQuUnit
			]);
		}
	}

	public function QuantityUnitEditForm(Request $request, Response $response, array $args)
	{
		if ($args['quantityunitId'] == 'new') {
			return $this->renderPage($response, 'quantityunitform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('quantity_units'),
				'pluralCount' => $this->getLocalizationService()->GetPluralCount(),
				'pluralRule' => $this->getLocalizationService()->GetPluralDefinition()
			]);
		} else {
			$quantityUnit = $this->getDatabase()->quantity_units($args['quantityunitId']);

			return $this->renderPage($response, 'quantityunitform', [
				'quantityUnit' => $quantityUnit,
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('quantity_units'),
				'pluralCount' => $this->getLocalizationService()->GetPluralCount(),
				'pluralRule' => $this->getLocalizationService()->GetPluralDefinition(),
				'defaultQuConversions' => $this->getDatabase()->quantity_unit_conversions()->where('from_qu_id = :1 AND product_id IS NULL', $quantityUnit->id),
				'quantityUnits' => $this->getDatabase()->quantity_units()
			]);
		}
	}

	public function QuantityUnitPluralFormTesting(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'quantityunitpluraltesting', [
			'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
		]);
	}

	public function QuantityUnitsList(Request $request, Response $response, array $args)
	{
		if (isset($request->getQueryParams()['include_disabled'])) {
			$quantityUnits = $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE');
		} else {
			$quantityUnits = $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
		}

		return $this->renderPage($response, 'quantityunits', [
			'quantityunits' => $quantityUnits,
			'userfields' => $this->getUserfieldsService()->GetFields('quantity_units'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('quantity_units')
		]);
	}

	public function ShoppingList(Request $request, Response $response, array $args)
	{
		$listId = 1;
		if (isset($request->getQueryParams()['list'])) {
			$listId = $request->getQueryParams()['list'];
		}

		return $this->renderPage($response, 'shoppinglist', [
			'listItems' => $this->getDatabase()->uihelper_shopping_list()->where('shopping_list_id = :1', $listId)->orderBy('product_name', 'COLLATE NOCASE'),
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityunits' => $this->getDatabase()->quantity_units()->orderBy('name', 'COLLATE NOCASE'),
			'missingProducts' => $this->getStockService()->GetMissingProducts(),
			'shoppingLists' => $this->getDatabase()->shopping_lists_view()->orderBy('name', 'COLLATE NOCASE'),
			'selectedShoppingListId' => $listId,
			'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
			'productUserfields' => $this->getUserfieldsService()->GetFields('products'),
			'productUserfieldValues' => $this->getUserfieldsService()->GetAllValues('products'),
			'productGroupUserfields' => $this->getUserfieldsService()->GetFields('product_groups'),
			'productGroupUserfieldValues' => $this->getUserfieldsService()->GetAllValues('product_groups'),
			'userfields' => $this->getUserfieldsService()->GetFields('shopping_list'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('shopping_list')
		]);
	}

	public function ShoppingListEditForm(Request $request, Response $response, array $args)
	{
		if ($args['listId'] == 'new') {
			return $this->renderPage($response, 'shoppinglistform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_lists')
			]);
		} else {
			return $this->renderPage($response, 'shoppinglistform', [
				'shoppingList' => $this->getDatabase()->shopping_lists($args['listId']),
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_lists')
			]);
		}
	}

	public function ShoppingListItemEditForm(Request $request, Response $response, array $args)
	{
		if ($args['itemId'] == 'new') {
			return $this->renderPage($response, 'shoppinglistitemform', [
				'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
				'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE'),
				'mode' => 'create',
				'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_list')
			]);
		} else {
			return $this->renderPage($response, 'shoppinglistitemform', [
				'listItem' => $this->getDatabase()->shopping_list($args['itemId']),
				'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
				'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE'),
				'mode' => 'edit',
				'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
				'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved(),
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_list')
			]);
		}
	}

	public function ShoppingListSettings(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'shoppinglistsettings', [
			'shoppingLists' => $this->getDatabase()->shopping_lists()->orderBy('name', 'COLLATE NOCASE')
		]);
	}

	public function ShoppingLocationEditForm(Request $request, Response $response, array $args)
	{
		if ($args['shoppingLocationId'] == 'new') {
			return $this->renderPage($response, 'shoppinglocationform', [
				'mode' => 'create',
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_locations')
			]);
		} else {
			return $this->renderPage($response, 'shoppinglocationform', [
				'shoppingLocation' => $this->getDatabase()->shopping_locations($args['shoppingLocationId']),
				'mode' => 'edit',
				'userfields' => $this->getUserfieldsService()->GetFields('shopping_locations')
			]);
		}
	}

	public function ShoppingLocationsList(Request $request, Response $response, array $args)
	{
		if (isset($request->getQueryParams()['include_disabled'])) {
			$shoppingLocations = $this->getDatabase()->shopping_locations()->orderBy('name', 'COLLATE NOCASE');
		} else {
			$shoppingLocations = $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
		}

		return $this->renderPage($response, 'shoppinglocations', [
			'shoppinglocations' => $shoppingLocations,
			'userfields' => $this->getUserfieldsService()->GetFields('shopping_locations'),
			'userfieldValues' => $this->getUserfieldsService()->GetAllValues('shopping_locations')
		]);
	}

	public function StockEntryEditForm(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'stockentryform', [
			'stockEntry' => $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch(),
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'userfields' => $this->getUserfieldsService()->GetFields('stock')
		]);
	}

	public function StockEntryGrocycodeImage(Request $request, Response $response, array $args)
	{
		$stockEntry = $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch();
		$gc = new Grocycode(Grocycode::PRODUCT, $stockEntry->product_id, [$stockEntry->stock_id]);
		return $this->ServeGrocycodeImage($request, $response, $gc);
	}

	public function StockEntryGrocycodeLabel(Request $request, Response $response, array $args)
	{
		$stockEntry = $this->getDatabase()->stock()->where('id', $args['entryId'])->fetch();
		return $this->renderPage($response, 'stockentrylabel', [
			'stockEntry' => $stockEntry,
			'product' => $this->getDatabase()->products($stockEntry->product_id),
		]);
	}

	public function StockSettings(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'stocksettings', [
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'productGroups' => $this->getDatabase()->product_groups()->where('active = 1')->orderBy('name', 'COLLATE NOCASE')
		]);
	}

	public function Stockentries(Request $request, Response $response, array $args)
	{
		$usersService = $this->getUsersService();
		$nextXDays = $usersService->GetUserSettings(GROCY_USER_ID)['stock_due_soon_days'];

		return $this->renderPage($response, 'stockentries', [
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityunits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'shoppinglocations' => $this->getDatabase()->shopping_locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'stockEntries' => $this->getDatabase()->uihelper_stock_entries()->orderBy('product_id'),
			'currentStockLocations' => $this->getStockService()->GetCurrentStockLocations(),
			'nextXDays' => $nextXDays,
			'userfieldsProducts' => $this->getUserfieldsService()->GetFields('products'),
			'userfieldValuesProducts' => $this->getUserfieldsService()->GetAllValues('products'),
			'userfieldsStock' => $this->getUserfieldsService()->GetFields('stock'),
			'userfieldValuesStock' => $this->getUserfieldsService()->GetAllValues('stock')
		]);
	}

	public function Transfer(Request $request, Response $response, array $args)
	{
		return $this->renderPage($response, 'transfer', [
			'products' => $this->getDatabase()->products()->where('active = 1')->where('no_own_stock = 0 AND id IN (SELECT product_id from stock_current WHERE amount_aggregated > 0)')->orderBy('name', 'COLLATE NOCASE'),
			'barcodes' => $this->getDatabase()->product_barcodes_comma_separated(),
			'locations' => $this->getDatabase()->locations()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnitConversionsResolved' => $this->getDatabase()->cache__quantity_unit_conversions_resolved()
		]);
	}

	public function JournalSummary(Request $request, Response $response, array $args)
	{
		$entries = $this->getDatabase()->uihelper_stock_journal_summary();
		if (isset($request->getQueryParams()['product_id'])) {
			$entries = $entries->where('product_id', $request->getQueryParams()['product_id']);
		}
		if (isset($request->getQueryParams()['user_id'])) {
			$entries = $entries->where('user_id', $request->getQueryParams()['user_id']);
		}
		if (isset($request->getQueryParams()['transaction_type'])) {
			$entries = $entries->where('transaction_type', $request->getQueryParams()['transaction_type']);
		}

		$usersService = $this->getUsersService();
		return $this->renderPage($response, 'stockjournalsummary', [
			'entries' => $entries,
			'products' => $this->getDatabase()->products()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'users' => $usersService->GetUsersAsDto(),
			'transactionTypes' => GetClassConstants('\Grocy\Services\StockService', 'TRANSACTION_TYPE_')
		]);
	}

	public function QuantityUnitConversionsResolved(Request $request, Response $response, array $args)
	{
		$product = null;
		if (isset($request->getQueryParams()['product'])) {
			$product = $this->getDatabase()->products($request->getQueryParams()['product']);
			$quantityUnitConversionsResolved = $this->getDatabase()->cache__quantity_unit_conversions_resolved()->where('product_id', $product->id);
		} else {
			$quantityUnitConversionsResolved = $this->getDatabase()->cache__quantity_unit_conversions_resolved()->where('product_id IS NULL');
		}

		return $this->renderPage($response, 'quantityunitconversionsresolved', [
			'product' => $product,
			'quantityUnits' => $this->getDatabase()->quantity_units()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
			'quantityUnitConversionsResolved' => $quantityUnitConversionsResolved
		]);
	}
}
