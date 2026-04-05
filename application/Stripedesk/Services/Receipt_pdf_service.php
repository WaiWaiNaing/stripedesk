<?php

namespace Stripedesk\Services;

use Stripedesk\Contracts\Pdf_renderer_interface;

/**
 * Orchestrates receipt PDF generation (depends on abstractions; collaborators are swappable for tests).
 */
final class Receipt_pdf_service
{
    /** @var Receipt_html_document_builder */
    private $html_builder;

    /** @var Pdf_renderer_interface */
    private $pdf_renderer;

    public function __construct(
        ?Receipt_html_document_builder $html_builder = null,
        ?Pdf_renderer_interface $pdf_renderer = null
    ) {
        $this->html_builder = $html_builder ?: new Receipt_html_document_builder();
        $this->pdf_renderer = $pdf_renderer ?: new Dompdf_pdf_renderer();
    }

    /**
     * @param array $data Receipt detail array plus optional line_items
     *
     * @return string PDF binary
     */
    public function render_binary(array $data)
    {
        $html = $this->html_builder->build($data);

        return $this->pdf_renderer->render_from_html($html);
    }
}
