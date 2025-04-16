ALTER TABLE notifications
ADD CONSTRAINT chk_notification_message_min_length
CHECK (CHAR_LENGTH(message) > 5);