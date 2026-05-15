USE pos_db;

DROP TRIGGER IF EXISTS after_sale_insert;
DELIMITER $$

CREATE TRIGGER after_sale_insert
AFTER INSERT ON sales_items
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;

    -- Get current stock
    SELECT stock INTO current_stock
    FROM products
    WHERE id = NEW.product_id;

    -- Update stock
    UPDATE products
    SET stock = current_stock - NEW.quantity,
        status = IF(current_stock - NEW.quantity <= 0, 'unavailable', 'available')
    WHERE id = NEW.product_id;

    -- Log inventory movement
    INSERT INTO inventory_logs (product_id, user_id, type, quantity, reference_id)
    SELECT NEW.product_id, s.user_id, 'OUT', NEW.quantity, NEW.sale_id
    FROM sales s
    WHERE s.id = NEW.sale_id;

END $$

DELIMITER ;

DROP TRIGGER IF EXISTS after_sale_item_delete;
DELIMITER $$

CREATE TRIGGER after_sale_item_delete
AFTER DELETE ON sales_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock + OLD.quantity,
        status = 'available'
    WHERE id = OLD.product_id;
END $$

DELIMITER ;