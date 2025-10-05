<?php
// PayHere Payment Gateway Integration for RentFinder SL

class PayHereGateway
{
    private $merchantId;
    private $merchantSecret;
    private $sandboxMode;
    private $baseUrl;

    public function __construct($merchantId, $merchantSecret, $sandboxMode = true)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
        $this->sandboxMode = $sandboxMode;
        $this->baseUrl = $sandboxMode ? 'https://sandbox.payhere.lk/pay/checkout' : 'https://www.payhere.lk/pay/checkout';
    }

    /**
     * Generate payment form data for PayHere
     */
    public function generatePaymentData($orderData)
    {
        $merchantId = $this->merchantId;
        $merchantSecret = $this->merchantSecret;
        $orderId = $orderData['order_id'];
        $amount = number_format($orderData['amount'], 2, '.', '');
        $currency = $orderData['currency'] ?? 'LKR';
        $firstname = $orderData['firstname'];
        $lastname = $orderData['lastname'];
        $email = $orderData['email'];
        $phone = $orderData['phone'];
        $address = $orderData['address'];
        $city = $orderData['city'];
        $country = $orderData['country'] ?? 'Sri Lanka';
        $items = $orderData['items'];
        $custom1 = $orderData['custom1'] ?? '';
        $custom2 = $orderData['custom2'] ?? '';

        // Create hash for security
        $hash = strtoupper(
            md5(
                $merchantId .
                    $orderId .
                    $amount .
                    $currency .
                    strtoupper(md5($merchantSecret))
            )
        );

        return [
            'merchant_id' => $merchantId,
            'return_url' => $orderData['return_url'],
            'cancel_url' => $orderData['cancel_url'],
            'notify_url' => $orderData['notify_url'],
            'first_name' => $firstname,
            'last_name' => $lastname,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'country' => $country,
            'order_id' => $orderId,
            'items' => $items,
            'currency' => $currency,
            'amount' => $amount,
            'hash' => $hash,
            'custom_1' => $custom1,
            'custom_2' => $custom2
        ];
    }

    /**
     * Generate payment form HTML
     */
    public function generatePaymentForm($orderData)
    {
        $paymentData = $this->generatePaymentData($orderData);

        $formHtml = '<form action="' . $this->baseUrl . '" method="post" id="payhere-payment-form">';

        foreach ($paymentData as $key => $value) {
            if (is_array($value)) {
                // Handle items array
                foreach ($value as $index => $item) {
                    $formHtml .= '<input type="hidden" name="item_name_' . ($index + 1) . '" value="' . htmlspecialchars($item['name']) . '">';
                    $formHtml .= '<input type="hidden" name="item_number_' . ($index + 1) . '" value="' . htmlspecialchars($item['id']) . '">';
                    $formHtml .= '<input type="hidden" name="amount_' . ($index + 1) . '" value="' . htmlspecialchars($item['amount']) . '">';
                    $formHtml .= '<input type="hidden" name="quantity_' . ($index + 1) . '" value="' . htmlspecialchars($item['quantity']) . '">';
                }
            } else {
                $formHtml .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
            }
        }

        $formHtml .= '</form>';

        return $formHtml;
    }

    /**
     * Verify payment callback
     */
    public function verifyPayment($postData)
    {
        $merchantId = $postData['merchant_id'] ?? '';
        $orderId = $postData['order_id'] ?? '';
        $payhereAmount = $postData['payhere_amount'] ?? '';
        $payhereCurrency = $postData['payhere_currency'] ?? '';
        $statusCode = $postData['status_code'] ?? '';
        $md5sig = $postData['md5sig'] ?? '';

        // Verify merchant ID
        if ($merchantId !== $this->merchantId) {
            return false;
        }

        // Generate verification hash
        $merchantSecret = strtoupper(md5($this->merchantSecret));
        $localMd5sig = strtoupper(md5($merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . $merchantSecret));

        // Verify hash
        if ($md5sig !== $localMd5sig) {
            return false;
        }

        return [
            'order_id' => $orderId,
            'amount' => $payhereAmount,
            'currency' => $payhereCurrency,
            'status' => $statusCode,
            'payment_id' => $postData['payment_id'] ?? '',
            'method' => $postData['method'] ?? '',
            'status_message' => $postData['status_message'] ?? ''
        ];
    }

    /**
     * Create recurring payment subscription
     */
    public function createRecurringPayment($subscriptionData)
    {
        // For recurring payments, we'll use PayHere's subscription API
        // This is a simplified implementation
        $orderData = [
            'order_id' => $subscriptionData['subscription_id'],
            'amount' => $subscriptionData['amount'],
            'currency' => 'LKR',
            'firstname' => $subscriptionData['firstname'],
            'lastname' => $subscriptionData['lastname'],
            'email' => $subscriptionData['email'],
            'phone' => $subscriptionData['phone'],
            'address' => $subscriptionData['address'],
            'city' => $subscriptionData['city'],
            'items' => [
                [
                    'id' => 'recurring_rent',
                    'name' => 'Monthly Rent Payment',
                    'amount' => $subscriptionData['amount'],
                    'quantity' => 1
                ]
            ],
            'return_url' => $subscriptionData['return_url'],
            'cancel_url' => $subscriptionData['cancel_url'],
            'notify_url' => $subscriptionData['notify_url'],
            'custom1' => 'recurring',
            'custom2' => $subscriptionData['rental_agreement_id']
        ];

        return $this->generatePaymentForm($orderData);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($orderId)
    {
        // This would typically make an API call to PayHere
        // For now, we'll return a mock response
        return [
            'order_id' => $orderId,
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'LKR'
        ];
    }
}

// PayHere Configuration
$payhereConfig = [
    'merchant_id' => '1217129', // Replace with your actual merchant ID
    'merchant_secret' => '8aBcD3fG6hI9jK2lM5nP8qR1sT4vW7xY0zA', // Replace with your actual secret
    'sandbox_mode' => true, // Set to false for production
    'return_url' => 'http://localhost/Chinthaka/payment_success.php',
    'cancel_url' => 'http://localhost/Chinthaka/payment_cancel.php',
    'notify_url' => 'http://localhost/Chinthaka/payment_notify.php'
];

// Initialize PayHere gateway
$payhere = new PayHereGateway(
    $payhereConfig['merchant_id'],
    $payhereConfig['merchant_secret'],
    $payhereConfig['sandbox_mode']
);
