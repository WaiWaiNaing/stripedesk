<?php

namespace Stripedesk\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Stripedesk\Contracts\Pdf_renderer_interface;

/**
 * Dompdf-backed implementation of {@see Pdf_renderer_interface} (Open/Closed: swap renderer without changing callers).
 */
final class Dompdf_pdf_renderer implements Pdf_renderer_interface
{
    public function render_from_html($html)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
