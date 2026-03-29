<?php

namespace Stripedesk\Dto;

final class Invoice_detail_dto
{
    private $invoice;
    private $order;
    private $lines;

    public function __construct(Invoice_summary_dto $invoice, Order_summary_dto $order, array $lines)
    {
        $this->invoice = $invoice;
        $this->order = $order;
        $this->lines = $lines;
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

        return array(
            'invoice' => $this->invoice->to_array(),
            'order' => $this->order->to_array(),
            'lines' => $line_arrays,
        );
    }
}
