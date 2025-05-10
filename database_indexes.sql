-- Sa requests table
CREATE INDEX idx_requests_tracking_number ON requests(tracking_number);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requests_request_type ON requests(request_type);
CREATE INDEX idx_requests_created_at ON requests(created_at);
-- Sa users table
CREATE INDEX idx_users_full_name ON users(full_name);
CREATE INDEX idx_users_email ON users(email);
-- Sa notifications table
CREATE INDEX idx_notifications_user_id_is_read ON notifications(user_id, is_read);
