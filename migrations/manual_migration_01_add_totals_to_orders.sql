-- SQL Migration to add de-normalized total_amount and total_weight to orders table

ALTER TABLE `orders`
ADD COLUMN `total_amount` DECIMAL(12,2) NOT NULL DEFAULT '0.00' AFTER `delivery_price_euro`,
ADD COLUMN `total_weight` DECIMAL(12,3) NOT NULL DEFAULT '0.000' AFTER `total_amount`;

-- Initialize total_amount and total_weight with current sums from articles
UPDATE `orders` o
SET
    o.total_amount = (
        SELECT IFNULL(SUM(oa.amount * oa.price), 0.00)
        FROM `orders_article` oa
        WHERE oa.orders_id = o.id
    ),
    o.total_weight = (
        SELECT IFNULL(SUM(oa.amount * oa.weight), 0.000)
        FROM `orders_article` oa
        WHERE oa.orders_id = o.id
    );
