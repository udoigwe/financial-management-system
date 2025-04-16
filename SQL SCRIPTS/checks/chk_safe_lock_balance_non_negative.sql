ALTER TABLE safe_lock
ADD CONSTRAINT chk_safe_lock_balance_non_negative
CHECK (balance >= 0);