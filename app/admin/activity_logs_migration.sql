-- Activity Logs Table for AgriConnect
-- Tracks all user actions across the platform

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who performed the action',
  `action` varchar(50) NOT NULL COMMENT 'e.g., CREATE, UPDATE, DELETE, LOGIN, POST, PURCHASE',
  `table_name` varchar(50) NOT NULL COMMENT 'e.g., users, products, posts, transactions',
  `record_id` int(11) DEFAULT NULL COMMENT 'ID of the affected record',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device info',
  `data` json DEFAULT NULL COMMENT 'Additional context (e.g., field names changed)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `table_name` (`table_name`),
  KEY `created_at` (`created_at`),
  KEY `search_idx` (`action`, `user_id`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create indexes for efficient querying
CREATE INDEX idx_activity_logs_user_created ON activity_logs(user_id, created_at DESC);
CREATE INDEX idx_activity_logs_action_created ON activity_logs(action, created_at DESC);
CREATE INDEX idx_activity_logs_table_created ON activity_logs(table_name, created_at DESC);
