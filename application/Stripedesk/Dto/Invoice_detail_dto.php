<?php

namespace Stripedesk\Dto;

final class Invoice_detail_dto
{
    private $invoice;
    private $order;
    private $lines;
    private $receipt;

    /**
     * @param array|null $receipt When set: array with keys id, receipt_number (payment receipt if issued)
     */
    public function __construct(Invoice_summary_dto $invoice, Order_summary_dto $order, array $lines, $receipt = null)
    {
        $this->invoice = $invoice;
        $this->order = $order;
        $this->lines = $lines;
        $this->receipt = is_array($receipt) ? $receipt : null;
    }

    public function to_array()
    {
        $line_arrays = array();
        foreach ($this->lines as $line)
        {
            if ($line instanceof Invoice_order_item_line_dto)
            {
                $line_arrays[] = $line->to_array();
            }
        }

        $out = array(
            'invoice' => $this->invoice->to_array(),
            'order' => $this->order->to_array(),
            'lines' => $line_arrays,
        );
        if ($this->receipt !== null)
        {
            $out['receipt'] = $this->receipt;
        }

        return $out;
    }
}
