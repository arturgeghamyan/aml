<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function employeeStats(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'employee') {
            abort(403, 'Only employees can view stats.');
        }

        $bestSelling = DB::select("
            SELECT
                oi.product_id,
                p.product_name,
                SUM(oi.quantity) AS total_units_sold
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            GROUP BY oi.product_id, p.product_name
            ORDER BY total_units_sold DESC
            LIMIT 10
        ");

        $monthlyRevenue = DB::select("
            SELECT
                EXTRACT(YEAR FROM o.order_date)  AS year,
                EXTRACT(MONTH FROM o.order_date) AS month,
                SUM(oi.subtotal)                 AS total_revenue
            FROM order_table o
            JOIN order_item oi ON o.order_id = oi.order_id
            GROUP BY year, month
            ORDER BY year, month
        ");

        $topSellers = DB::select("
            SELECT
                user_id,
                seller_company_name,
                total_sales
            FROM seller
            ORDER BY total_sales DESC
            LIMIT 5
        ");

        $topRatedProducts = DB::select("
            SELECT
                p.product_id,
                p.product_name,
                ROUND(AVG(r.review_rating), 2) AS avg_rating,
                COUNT(r.review_id)             AS reviews_count
            FROM product p
            JOIN order_item oi
                ON oi.product_id = p.product_id
            JOIN review r
                ON r.order_id      = oi.order_id
               AND r.order_item_id = oi.order_item_id
            GROUP BY
                p.product_id,
                p.product_name
            HAVING
                COUNT(r.review_id) >= 1
                AND AVG(r.review_rating) >= 4
            ORDER BY
                avg_rating DESC,
                reviews_count DESC
        ");

        $priceFluctuation = DB::select("
            WITH RankedProductSales AS (
                SELECT
                    p.product_id,
                    p.product_name,
                    oi.unit_price,
                    ot.order_date,
                 ROW_NUMBER() OVER (
                 PARTITION BY p.product_id, p.user_id
                 ORDER BY ot.order_date DESC
                    ) AS price_rank
                FROM product p
                JOIN order_item oi ON p.product_id = oi.product_id
                JOIN order_table ot ON oi.order_id = ot.order_id
            )
            SELECT
                product_id,
                product_name,
                unit_price AS recent_sold_price,
                order_date
            FROM
                RankedProductSales
            WHERE
                price_rank <= 3
            ORDER BY
                product_id,
                order_date DESC
        ");

        $customerSummary = DB::select("
            SELECT
                cu.user_id AS customer_id,
                u.first_name AS customer_name,
                COUNT(DISTINCT o.order_id) AS total_orders,
                SUM(oi.subtotal)          AS total_spent,
                COUNT(DISTINCT r.review_id) AS total_reviews_written
            FROM customer cu
            JOIN user_table u ON cu.user_id = u.user_id
            LEFT JOIN order_table o ON cu.user_id = o.customer_id
            LEFT JOIN order_item oi ON o.order_id = oi.order_id
            LEFT JOIN review r
                   ON r.order_id      = oi.order_id
                  AND r.order_item_id = oi.order_item_id
                  AND r.user_id       = cu.user_id
            GROUP BY cu.user_id, u.first_name
            ORDER BY total_spent DESC
        ");

        $lowStock = DB::select("
            SELECT
                w.warehouse_name,
                w.stock_amount AS total_stock,
                CASE 
                    WHEN w.stock_amount = 0      THEN 'Out of Stock'
                    WHEN w.stock_amount < 10     THEN 'Low Stock (' || w.stock_amount || ' left)'
                    ELSE 'In Stock (' || w.stock_amount || ' available)'
                END AS stock_status
            FROM warehouse w
            ORDER BY w.stock_amount ASC
        ");

        $deliveryReport = DB::select("
            SELECT o.order_id, o.order_date, w.warehouse_name, c.delivery_status, c.delivered_at, AVG(c.delivered_at - o.order_date) OVER() AS avg_delivery_days 
            FROM order_table o LEFT JOIN carrier c ON o.order_id = c.carrier_id 
            LEFT JOIN warehouse w ON c.warehouse_id = w.warehouse_id 
            ORDER BY o.order_id 
            LIMIT 5
        ");

        $refundAnalysis = DB::select("
            SELECT
                rr.reason       AS return_reason,
                COUNT(r.refund_id) AS total_refunds,
                SUM(r.amount)      AS total_refund_amount,
                p.product_name     AS affected_products
            FROM refund r
            JOIN return_request rr
                  ON r.return_request_id = rr.return_request_id
            JOIN order_item oi
                  ON rr.order_id      = oi.order_id
                 AND rr.order_item_id = oi.order_item_id
            JOIN product p
                  ON oi.product_id = p.product_id
            GROUP BY rr.reason, p.product_name
            ORDER BY total_refund_amount DESC
        ");

        return response()->json([
            'best_selling' => $bestSelling,
            'monthly_revenue' => $monthlyRevenue,
            'top_sellers' => $topSellers,
            'top_rated_products' => $topRatedProducts,
            'price_fluctuation' => $priceFluctuation,
            'customer_summary' => $customerSummary,
            'low_stock' => $lowStock,
            'delivery_report' => $deliveryReport,
            'refund_analysis' => $refundAnalysis,
        ]);
    }
}
