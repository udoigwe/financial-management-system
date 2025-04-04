DELIMITER //

CREATE PROCEDURE verifyOTP(
    IN p_account_id INT,
    IN p_otp INT
)
BEGIN
    DECLARE otp_count INT;
    DECLARE otp_expired INT;
    
    -- Check if the OTP exists
    SELECT COUNT(*) INTO otp_count 
    FROM otp
    WHERE account_id = p_account_id 
    AND otp = p_otp;
    
    IF otp_count = 0 THEN
        SELECT 'OTP_NOT_FOUND' AS status;
    ELSE
        -- Check if the OTP has expired
        SELECT COUNT(*) INTO otp_expired 
        FROM otp
        WHERE account_id = p_account_id 
        AND otp = p_otp 
        AND expires_at <= NOW();
        
        IF otp_expired > 0 THEN
            SELECT 'OTP_EXPIRED' AS status;
        ELSE
            -- OTP is valid, delete it and return success status
            DELETE FROM otp WHERE account_id = p_account_id;
            SELECT 'OTP_VALID' AS status;
        END IF;
    END IF;
END //

DELIMITER ;
