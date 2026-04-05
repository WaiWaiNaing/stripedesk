<?php

namespace Stripedesk\Contracts;

/**
 * Renders HTML into a PDF document (Dependency Inversion: receipt code depends on this abstraction).
 */
interface Pdf_renderer_interface
{
    /**
     * @param string $html UTF-8 HTML document
     *
     * @return string Raw PDF bytes
     */
    public function render_from_html($html);
}
