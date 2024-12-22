<?php
namespace Opencart\Admin\Model\Marketing;
/**
 * Class Coupon
 *
 * Can be called from $this->load->model('marketing/coupon');
 *
 * @package Opencart\Admin\Model\Marketing
 */
class Coupon extends \Opencart\System\Engine\Model {
	/**
	 * Add Coupon
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return int
	 */
	public function addCoupon(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `code` = '" . $this->db->escape((string)$data['code']) . "', `discount` = '" . (float)$data['discount'] . "', `type` = '" . $this->db->escape((string)$data['type']) . "', `total` = '" . (float)$data['total'] . "', `logged` = '" . (isset($data['logged']) ? (bool)$data['logged'] : 0) . "', `shipping` = '" . (isset($data['shipping']) ? (bool)$data['shipping'] : 0) . "', `date_start` = '" . $this->db->escape((string)$data['date_start']) . "', `date_end` = '" . $this->db->escape((string)$data['date_end']) . "', `uses_total` = '" . (int)$data['uses_total'] . "', `uses_customer` = '" . (int)$data['uses_customer'] . "', `status` = '" . (bool)($data['status'] ?? 0) . "', `date_added` = NOW()");

		$coupon_id = $this->db->getLastId();

		if (isset($data['coupon_product'])) {
			foreach ($data['coupon_product'] as $product_id) {
				$this->addProduct($coupon_id, $product_id);
			}
		}

		if (isset($data['coupon_category'])) {
			foreach ($data['coupon_category'] as $category_id) {
				$this->addCategory($coupon_id, $category_id);
			}
		}

		return $coupon_id;
	}

	/**
	 * Edit Coupon
	 *
	 * @param int                  $coupon_id primary key of the coupon record
	 * @param array<string, mixed> $data      array of data
	 *
	 * @return void
	 */
	public function editCoupon(int $coupon_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "coupon` SET `name` = '" . $this->db->escape((string)$data['name']) . "', `code` = '" . $this->db->escape((string)$data['code']) . "', `discount` = '" . (float)$data['discount'] . "', `type` = '" . $this->db->escape((string)$data['type']) . "', `total` = '" . (float)$data['total'] . "', `logged` = '" . (isset($data['logged']) ? (bool)$data['logged'] : 0) . "', `shipping` = '" . (isset($data['shipping']) ? (bool)$data['shipping'] : 0) . "', `date_start` = '" . $this->db->escape((string)$data['date_start']) . "', `date_end` = '" . $this->db->escape((string)$data['date_end']) . "', `uses_total` = '" . (int)$data['uses_total'] . "', `uses_customer` = '" . (int)$data['uses_customer'] . "', `status` = '" . (bool)($data['status'] ?? 0) . "' WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		$this->deleteProducts($coupon_id);

		if (isset($data['coupon_product'])) {
			foreach ($data['coupon_product'] as $product_id) {
				$this->addProduct($coupon_id, $product_id);
			}
		}

		$this->deleteCategories($coupon_id);

		if (isset($data['coupon_category'])) {
			foreach ($data['coupon_category'] as $category_id) {
				$this->addCategory($coupon_id, $category_id);
			}
		}
	}

	/**
	 * Delete Coupon
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return void
	 */
	public function deleteCoupon(int $coupon_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon` WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		$this->deleteProducts($coupon_id);
		$this->deleteCategories($coupon_id);
		$this->deleteHistories($coupon_id);
	}

	/**
	 * Get Coupon
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return array<string, mixed> coupon record that has coupon ID
	 */
	public function getCoupon(int $coupon_id): array {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "coupon` WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		return $query->row;
	}

	/**
	 * Get Coupon By Code
	 *
	 * @param string $code
	 *
	 * @return array<string, mixed>
	 */
	public function getCouponByCode(string $code): array {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "coupon` WHERE `code` = '" . $this->db->escape($code) . "'");

		return $query->row;
	}

	/**
	 * Get Coupons
	 *
	 * @param array<string, mixed> $data array of filters
	 *
	 * @return array<int, array<string, mixed>> coupon records
	 */
	public function getCoupons(array $data = []): array {
		$sql = "SELECT `coupon_id`, `name`, `code`, `discount`, `date_start`, `date_end`, `status` FROM `" . DB_PREFIX . "coupon`";

		$sort_data = [
			'name',
			'code',
			'discount',
			'date_start',
			'date_end',
			'status'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `name`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Add Product
	 *
	 * @param int $coupon_id  primary key of the coupon record
	 * @param int $product_id primary key of the product record
	 *
	 * @return void
	 */
	public function addProduct(int $coupon_id, int $product_id): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_product` SET `coupon_id` = '" . (int)$coupon_id . "', `product_id` = '" . (int)$product_id . "'");
	}

	/**
	 * Delete Products
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return void
	 */
	public function deleteProducts(int $coupon_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_product` WHERE `coupon_id` = '" . (int)$coupon_id . "'");
	}

	/**
	 * Delete Products By Product ID
	 *
	 * @param int $product_id primary key of the product record
	 *
	 * @return void
	 */
	public function deleteProductsByProductId(int $product_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_product` WHERE `product_id` = '" . (int)$product_id . "'");
	}

	/**
	 * Get Products
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return array<int, int> product records that have coupon ID
	 */
	public function getProducts(int $coupon_id): array {
		$coupon_product_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		foreach ($query->rows as $result) {
			$coupon_product_data[] = $result['product_id'];
		}

		return $coupon_product_data;
	}

	/**
	 * Add Category
	 *
	 * @param int $coupon_id   primary key of the coupon record
	 * @param int $category_id primary key of the category record
	 *
	 * @return void
	 */
	public function addCategory(int $coupon_id, int $category_id): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_category` SET `coupon_id` = '" . (int)$coupon_id . "', `category_id` = '" . (int)$category_id . "'");
	}

	/**
	 * Delete Categories
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return void
	 */
	public function deleteCategories(int $coupon_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_category` WHERE `coupon_id` = '" . (int)$coupon_id . "'");
	}

	/**
	 * Delete Categories By Category ID
	 *
	 * @param int $category_id primary key of the category record
	 *
	 * @return void
	 */
	public function deleteCategoriesByCategoryId(int $category_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_category` WHERE `category_id` = '" . (int)$category_id . "'");
	}

	/**
	 * Get Categories
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return array<int, int> category records that have coupon ID
	 */
	public function getCategories(int $coupon_id): array {
		$coupon_category_data = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		foreach ($query->rows as $result) {
			$coupon_category_data[] = $result['category_id'];
		}

		return $coupon_category_data;
	}

	/**
	 * Get Total Coupons
	 *
	 * @return int total number of coupon records
	 */
	public function getTotalCoupons(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "coupon`");

		return (int)$query->row['total'];
	}

	/**
	 * Get Histories
	 *
	 * @param int $coupon_id primary key of the coupon record
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>> history records that have coupon ID
	 */
	public function getHistories(int $coupon_id, int $start = 0, int $limit = 10): array {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT `ch`.`order_id`, CONCAT(`c`.`firstname`, ' ', `c`.`lastname`) AS `customer`, `ch`.`amount`, `ch`.`date_added` FROM `" . DB_PREFIX . "coupon_history` `ch` LEFT JOIN `" . DB_PREFIX . "customer` `c` ON (`ch`.`customer_id` = `c`.`customer_id`) WHERE `ch`.`coupon_id` = '" . (int)$coupon_id . "' ORDER BY `ch`.`date_added` ASC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	/**
	 * Delete Coupon Histories
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return void
	 */
	public function deleteHistories(int $coupon_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_history` WHERE `coupon_id` = '" . (int)$coupon_id . "'");
	}

	/**
	 * Get Total Histories
	 *
	 * @param int $coupon_id primary key of the coupon record
	 *
	 * @return int total number of history records
	 */
	public function getTotalHistories(int $coupon_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "coupon_history` WHERE `coupon_id` = '" . (int)$coupon_id . "'");

		return (int)$query->row['total'];
	}
}
