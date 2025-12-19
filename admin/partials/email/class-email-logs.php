<?php

class Aben_Email_Logs
{
    private $table_name;
    private $cache_group_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name       = $wpdb->prefix . 'aben_email_logs';
        $this->cache_group_name = 'aben_email_logs';
    }

    /**
     * Log an email entry into the database.
     *
     * @param string $to The recipient of the email.
     * @param string $subject The subject of the email.
     * @param string $message The message content of the email.
     * @param string $status The status of the email (e.g., "sent", "failed").
     */
    public function log_email($to, $subject, $message, $status, $error_message = null)
    {
        global $wpdb;

        // Sanitize input data
        $data = [
            'email_to'      => sanitize_email($to),
            'subject'       => sanitize_text_field($subject),
            'message'       => wp_kses_post($message),
            'status'        => sanitize_text_field($status),
            'error_message' => $error_message ? sanitize_textarea_field($error_message) : null,
            'sent_at'       => current_time('mysql'),
        ];

        // Insert the log entry into the table
        $wpdb->insert(
            $this->table_name,
            $data,
            [
                '%s', // email_to
                '%s', // subject
                '%s', // message
                '%s', // status
                '%s', // error_message
                '%s', // sent_at
            ]
        );

        wp_cache_delete('all_logs', $this->cache_group_name);
    }

    /**
     * Clear logs older than 30 days.
     */
    public function clear_old_logs()
    {
        global $wpdb;
        $query = "DELETE FROM {$this->table_name} WHERE sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        // Delete logs older than 30 days
        $wpdb->query($query);
    }

    /**
     * Retrieve email logs from the database with optional filters and pagination.
     *
     * @param int $limit The number of records to retrieve (default is 10).
     * @param int $offset The offset (default is 0).
     * @param array $filters Optional filters to apply to the query.
     *
     * @return array The retrieved email logs.
     */
    public function get_logs($limit = 10, $offset = 0, $filters = [])
    {
        global $wpdb;

        // Initialize the WHERE clause and parameters
        $where  = '';
        $params = [];

        // Apply filters (if any)
        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
                if (! empty($value)) {
                    // For security, sanitize the filter value
                    $where .= " AND $key LIKE %s";
                    $params[] = '%' . sanitize_text_field($value) . '%'; // Use LIKE for partial matches
                }
            }
        }

        $cached_logs = wp_cache_get('all_logs', $this->cache_group_name);
        if (false !== $cached_logs) {
            return $cached_logs;
        }

        // Build the SQL query with filtering, pagination, and sorting by sent_at
        $query = "SELECT * FROM {$this->table_name} WHERE 1=1 $where ORDER BY sent_at DESC LIMIT %d OFFSET %d";

        // Add limit and offset to the query parameters
        $params[] = (int) $limit;
        $params[] = (int) $offset;

        // Prepare the query with the parameters
        $prepared_query = $wpdb->prepare($query, ...$params);
        $logs           = $wpdb->get_results($prepared_query);

        $cache_expire = 23 * HOUR_IN_SECONDS;
        wp_cache_set('all_logs', $logs, $this->cache_group_name, $cache_expire);

        // Execute the query and return the results
        return $logs;
    }

    /**
     * Get the total number of email logs.
     *
     * @param array $filters Optional filters to apply to the query.
     * @return int Total number of logs.
     */
    public function get_logs_count($filters = [])
    {
        global $wpdb;

        // Initialize the WHERE clause and parameters
        $where  = '';
        $params = [];

        // Apply filters (if any)
        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
                if (! empty($value)) {
                    $where .= " AND $key LIKE %s";
                    $params[] = '%' . sanitize_text_field($value) . '%';
                }
            }
        }

        // Query to count the total logs
        $query = "
        SELECT COUNT(*) FROM $this->table_name
        WHERE 1=1 $where
    ";

        // Execute the query and return the total count
        return (int) $wpdb->get_var($query);
    }
}
