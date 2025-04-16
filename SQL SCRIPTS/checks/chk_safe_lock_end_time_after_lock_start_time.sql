ALTER TABLE safe_lock
ADD CONSTRAINT chk_safe_lock_end_time_after_lock_start_time
CHECK (lock_end_time > lock_start_time);