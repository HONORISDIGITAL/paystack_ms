import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:22
* @route '/api/webhooks/paystack/payment'
*/
export const handlePaymentWebhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handlePaymentWebhook.url(options),
    method: 'post',
})

handlePaymentWebhook.definition = {
    methods: ["post"],
    url: '/api/webhooks/paystack/payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:22
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhook.url = (options?: RouteQueryOptions) => {
    return handlePaymentWebhook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:22
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handlePaymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:22
* @route '/api/webhooks/paystack/payment'
*/
const handlePaymentWebhookForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handlePaymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:22
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhookForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handlePaymentWebhook.url(options),
    method: 'post',
})

handlePaymentWebhook.form = handlePaymentWebhookForm

const PaystackWebhookController = { handlePaymentWebhook }

export default PaystackWebhookController