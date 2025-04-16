ALTER TABLE otp
ADD CONSTRAINT chk_expiry_time_after_created_time
CHECK (expires_at > created_at);