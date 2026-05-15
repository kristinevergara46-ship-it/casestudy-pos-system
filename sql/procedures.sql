USE pos_db;

DELIMITER //

DROP PROCEDURE IF EXISTS process_sale //

CREATE PROCEDURE process_sale(
    IN p_user_id INT,
    IN p_amount_paid DECIMAL(10,2),
    IN p_items JSON
)
BEGIN
    DECLARE v_sale_id INT;
    DECLARE v_total DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_change DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_item_count INT DEFAULT 0;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_stock INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Calculate total from items
    SET v_item_count = JSON_LENGTH(p_items);

    -- Validate stock first
    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));

        SELECT stock INTO v_stock FROM products WHERE id = v_product_id FOR UPDATE;

        IF v_stock < v_quantity THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for product';
        END IF;

        SET v_i = v_i + 1;
    END WHILE;

    -- Reset counter for calculating total
    SET v_i = 0;

    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));
        SET v_price = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].price')));
        SET v_total = v_total + (v_price * v_quantity);
        SET v_i = v_i + 1;
    END WHILE;

    SET v_change = p_amount_paid - v_total;

    -- Insert sale record
    INSERT INTO sales (user_id, total, payment, amount_paid, change_amount)
    VALUES (p_user_id, v_total, 'cash', p_amount_paid, v_change);

    SET v_sale_id = LAST_INSERT_ID();

    -- Insert sale items (trigger will handle stock deduction)
    SET v_i = 0;
    WHILE v_i < v_item_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].quantity')));
        SET v_price = JSON_UNQUOTE(JSON_EXTRACT(p_items, CONCAT('$[', v_i, '].price')));

        INSERT INTO sales_items (sale_id, product_id, quantity, price)
        VALUES (v_sale_id, v_product_id, v_quantity, v_price);

        SET v_i = v_i + 1;
    END WHILE;

    COMMIT;

    -- Return sale info
    SELECT v_sale_id AS sale_id, v_total AS total, v_change AS change_amount;
END //

-- Stock In Procedure
DROP PROCEDURE IF EXISTS stock_in //

CREATE PROCEDURE stock_in(
    IN p_product_id INT,
    IN p_user_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    UPDATE products SET stock = stock + p_quantity WHERE id = p_product_id;

    INSERT INTO inventory_logs (product_id, user_id, type, quantity)
    VALUES (p_product_id, p_user_id, 'IN', p_quantity);

    COMMIT;
END //

DELIMITER ;
