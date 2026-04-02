<?php

namespace Stripedesk\Services;

/**
 * Creates order + invoice from a cart without Stripe.
 */
final class Checkout_invoice_service
{
    private $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
        $this->ci->load->model('cart_model');
        $this->ci->load->model('cart_item_model');
        $this->ci->load->model('currency_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
        $this->ci->load->model('invoice_model');
    }

    /**
     * @return array{0:bool,1:\Stripedesk\Dto\Invoice_detail_dto|int|string}
     */
    public function create_invoice_from_cart($actor_user, $cart_id)
    {
        $cart_id = (int) $cart_id;
        if ($cart_id < 1)
        {
            return array(false, 'cart_id is required');
        }

        $cart = $this->ci->cart_model->find($cart_id);
        if (! $cart || $cart->deleted_at !== null)
        {
            return array(false, 'cart not found');
        }
        if ((int) $cart->user_id !== (int) $actor_user->id)
        {
            return array(false, 'not_cart_owner');
        }
        if ((string) $cart->status !== 'active')
        {
            return array(false, 'cart must be active to checkout');
        }
        if ($cart->expires_at !== null && strtotime((string) $cart->expires_at) < time())
        {
            return array(false, 'cart expired');
        }

        $cart_items = $this->ci->cart_item_model->list_with_product_names_for_cart($cart_id);
        if (empty($cart_items))
        {
            return array(false, 'cart has no items');
        }

        $currency_row = $this->ci->currency_model->find((int) $cart_items[0]->product_currency_id);
        $currency_id = $currency_row ? (int) $currency_row->id : 1;

        $this->ci->db->trans_start();

        $order_id = $this->ci->order_model->create(array(
            'user_id' => (int) $actor_user->id,
            'total_amount' => (string) $cart->total_amount,
            'currency_id' => $currency_id,
            'status' => 'pending',
            'created_by' => (int) $actor_user->id,
            'updated_by' => (int) $actor_user->id,
        ));
        if (! $order_id)
        {
            $this->ci->db->trans_rollback();
            return array(false, 'failed to create order');
        }

        foreach ($cart_items as $ci_item)
        {
            $ok = $this->ci->order_item_model->create(array(
                'order_id' => (int) $order_id,
                'product_id' => (int) $ci_item->product_id,
                'quantity' => (int) $ci_item->quantity,
                'unit_price' => (string) $ci_item->price,
                'created_by' => (int) $actor_user->id,
                'updated_by' => (int) $actor_user->id,
            ));
            if (! $ok)
            {
                $this->ci->db->trans_rollback();
                return array(false, 'failed to create order item');
            }
        }

        $invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $order_id, 5, '0', STR_PAD_LEFT);
        if ($this->ci->invoice_model->get_by_invoice_number($invoice_number))
        {
            $invoice_number .= '-' . substr(sha1((string) microtime(true)), 0, 6);
        }

        $invoice_id = $this->ci->invoice_model->create(array(
            'order_id' => (int) $order_id,
            'invoice_number' => $invoice_number,
            'total_amount' => (string) $cart->total_amount,
            'status' => 'pending',
            'due_date' => null,
            'created_by' => (int) $actor_user->id,
            'updated_by' => (int) $actor_user->id,
        ));
        if (! $invoice_id)
        {
            $this->ci->db->trans_rollback();
            return array(false, 'failed to create invoice');
        }

        if ( ! $this->ci->cart_model->update_status($cart_id, 'converted', (int) $actor_user->id))
        {
            $this->ci->db->trans_rollback();
            return array(false, 'failed to finalize cart');
        }

        $this->ci->db->trans_complete();
        if ($this->ci->db->trans_status() === false)
        {
            return array(false, 'checkout transaction failed');
        }

        $inv_svc = new Invoice_api_service($this->ci);
        $detail = $inv_svc->detail_for_actor($actor_user, (int) $invoice_id);
        if ($detail === null || $detail === false)
        {
            return array(true, (int) $invoice_id);
        }

        return array(true, $detail);
    }
}
