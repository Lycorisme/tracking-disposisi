<?php
// includes/pagination.php

class Pagination {
    private $currentPage;
    private $totalRows;
    private $perPage;
    private $totalPages;
    
    public function __construct($totalRows, $perPage = 10, $currentPage = 1) {
        $this->totalRows = (int)$totalRows;
        $this->perPage = (int)$perPage;
        $this->currentPage = max(1, (int)$currentPage);
        $this->totalPages = ceil($this->totalRows / $this->perPage);
        
        // Adjust current page if exceeds total pages
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }
    
    public function getOffset() {
        return ($this->currentPage - 1) * $this->perPage;
    }
    
    public function getLimit() {
        return $this->perPage;
    }
    
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    public function hasPages() {
        return $this->totalPages > 1;
    }
    
    public function hasPrevious() {
        return $this->currentPage > 1;
    }
    
    public function hasNext() {
        return $this->currentPage < $this->totalPages;
    }
    
    public function getInfo() {
        if ($this->totalRows == 0) {
            return "Tidak ada data";
        }
        
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->totalRows);
        
        return "Menampilkan {$start} - {$end} dari {$this->totalRows} data";
    }
    
    public function render($baseUrl, $queryParams = []) {
        if (!$this->hasPages()) {
            return '';
        }
        
        $html = '<div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">';
        
        // Info text
        $html .= '<div class="text-sm text-gray-700">' . $this->getInfo() . '</div>';
        
        // Pagination buttons
        $html .= '<div class="flex items-center space-x-2">';
        
        // Previous button
        if ($this->hasPrevious()) {
            $prevUrl = $this->buildUrl($baseUrl, $this->currentPage - 1, $queryParams);
            $html .= '<a href="' . $prevUrl . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Previous
                      </a>';
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                        Previous
                      </span>';
        }
        
        // Page numbers
        $html .= $this->renderPageNumbers($baseUrl, $queryParams);
        
        // Next button
        if ($this->hasNext()) {
            $nextUrl = $this->buildUrl($baseUrl, $this->currentPage + 1, $queryParams);
            $html .= '<a href="' . $nextUrl . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Next
                      </a>';
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                        Next
                      </span>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    private function renderPageNumbers($baseUrl, $queryParams) {
        $html = '';
        $maxButtons = 5;
        
        $start = max(1, $this->currentPage - floor($maxButtons / 2));
        $end = min($this->totalPages, $start + $maxButtons - 1);
        
        // Adjust start if end is at max
        if ($end - $start < $maxButtons - 1) {
            $start = max(1, $end - $maxButtons + 1);
        }
        
        // First page
        if ($start > 1) {
            $url = $this->buildUrl($baseUrl, 1, $queryParams);
            $html .= '<a href="' . $url . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>';
            
            if ($start > 2) {
                $html .= '<span class="px-2 py-2 text-sm text-gray-500">...</span>';
            }
        }
        
        // Page numbers
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">' . $i . '</span>';
            } else {
                $url = $this->buildUrl($baseUrl, $i, $queryParams);
                $html .= '<a href="' . $url . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">' . $i . '</a>';
            }
        }
        
        // Last page
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $html .= '<span class="px-2 py-2 text-sm text-gray-500">...</span>';
            }
            
            $url = $this->buildUrl($baseUrl, $this->totalPages, $queryParams);
            $html .= '<a href="' . $url . '" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">' . $this->totalPages . '</a>';
        }
        
        return $html;
    }
    
    private function buildUrl($baseUrl, $page, $queryParams = []) {
        $queryParams['page'] = $page;
        $queryString = http_build_query($queryParams);
        
        return $baseUrl . ($queryString ? '?' . $queryString : '');
    }
}