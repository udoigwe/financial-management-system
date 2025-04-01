DELIMITER //

CREATE PROCEDURE storeNotification(
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_message TEXT
)
BEGIN
    INSERT INTO notifications (user_id, title, message)
    VALUES (p_user_id, p_title, p_message);
END //

DELIMITER ;