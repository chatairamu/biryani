<?php

/**
 * Generates HTML for pagination links using Bootstrap 5 styles.
 *
 * @param int $current_page The current page number.
 * @param int $total_pages The total number of pages.
 * @param string $base_url The base URL for the pagination links.
 * @param array $params Additional query parameters to include in the links.
 * @return string The generated HTML for the pagination component.
 */
function generate_pagination_links($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination">';

    // --- Previous Button ---
    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
    $params['page'] = $current_page - 1;
    $html .= '<li class="page-item ' . $prev_disabled . '">';
    $html .= '<a class="page-link" href="' . $base_url . '?' . http_build_query($params) . '">Previous</a>';
    $html .= '</li>';

    // --- Page Number Links ---
    // This logic creates a window of page numbers around the current page
    $window = 2;
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $window && $i <= $current_page + $window)) {
            $active_class = ($i == $current_page) ? 'active' : '';
            $params['page'] = $i;
            $html .= '<li class="page-item ' . $active_class . '">';
            $html .= '<a class="page-link" href="' . $base_url . '?' . http_build_query($params) . '">' . $i . '</a>';
            $html .= '</li>';
        } elseif ($i == $current_page - $window - 1 || $i == $current_page + $window + 1) {
            // Add ellipsis for gaps
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // --- Next Button ---
    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    $params['page'] = $current_page + 1;
    $html .= '<li class="page-item ' . $next_disabled . '">';
    $html .= '<a class="page-link" href="' . $base_url . '?' . http_build_query($params) . '">Next</a>';
    $html .= '</li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Generates and stores a CSRF token in the session.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 * Dies with a 403 error if validation fails.
 * @param string $submitted_token The token from the form submission.
 */
function validate_csrf_token($submitted_token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        // Token is invalid or doesn't exist.
        http_response_code(403);
        die('CSRF validation failed.');
    }
    // Note: The token is NOT unset after validation. This allows the same token
    // to be used for multiple AJAX requests or forms on the same page.
    // A new token will be generated on the next full page load.
}
