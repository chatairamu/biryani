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
