import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:21
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
* @see app/Http/Controllers/Api/PaystackWebhookController.php:21
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhook.url = (options?: RouteQueryOptions) => {
    return handlePaymentWebhook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:21
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handlePaymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:21
* @route '/api/webhooks/paystack/payment'
*/
const handlePaymentWebhookForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handlePaymentWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handlePaymentWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:21
* @route '/api/webhooks/paystack/payment'
*/
handlePaymentWebhookForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handlePaymentWebhook.url(options),
    method: 'post',
})

handlePaymentWebhook.form = handlePaymentWebhookForm

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleTransferWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:163
* @route '/api/webhooks/paystack/transfer'
*/
export const handleTransferWebhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleTransferWebhook.url(options),
    method: 'post',
})

handleTransferWebhook.definition = {
    methods: ["post"],
    url: '/api/webhooks/paystack/transfer',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleTransferWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:163
* @route '/api/webhooks/paystack/transfer'
*/
handleTransferWebhook.url = (options?: RouteQueryOptions) => {
    return handleTransferWebhook.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleTransferWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:163
* @route '/api/webhooks/paystack/transfer'
*/
handleTransferWebhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleTransferWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleTransferWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:163
* @route '/api/webhooks/paystack/transfer'
*/
const handleTransferWebhookForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleTransferWebhook.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleTransferWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:163
* @route '/api/webhooks/paystack/transfer'
*/
handleTransferWebhookForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleTransferWebhook.url(options),
    method: 'post',
})

handleTransferWebhook.form = handleTransferWebhookForm

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/generic'
*/
const handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.url(options),
    method: 'post',
})

handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.definition = {
    methods: ["post"],
    url: '/api/webhooks/paystack/generic',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/generic'
*/
handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.url = (options?: RouteQueryOptions) => {
    return handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/generic'
*/
handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/generic'
*/
const handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/generic'
*/
handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.url(options),
    method: 'post',
})

handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267.form = handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267Form
/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/test'
*/
const handleGenericWebhook41beab3a755721602a8faa6628d20a26 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleGenericWebhook41beab3a755721602a8faa6628d20a26.url(options),
    method: 'post',
})

handleGenericWebhook41beab3a755721602a8faa6628d20a26.definition = {
    methods: ["post"],
    url: '/api/webhooks/paystack/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/test'
*/
handleGenericWebhook41beab3a755721602a8faa6628d20a26.url = (options?: RouteQueryOptions) => {
    return handleGenericWebhook41beab3a755721602a8faa6628d20a26.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/test'
*/
handleGenericWebhook41beab3a755721602a8faa6628d20a26.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleGenericWebhook41beab3a755721602a8faa6628d20a26.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/test'
*/
const handleGenericWebhook41beab3a755721602a8faa6628d20a26Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleGenericWebhook41beab3a755721602a8faa6628d20a26.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\PaystackWebhookController::handleGenericWebhook
* @see app/Http/Controllers/Api/PaystackWebhookController.php:282
* @route '/api/webhooks/paystack/test'
*/
handleGenericWebhook41beab3a755721602a8faa6628d20a26Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: handleGenericWebhook41beab3a755721602a8faa6628d20a26.url(options),
    method: 'post',
})

handleGenericWebhook41beab3a755721602a8faa6628d20a26.form = handleGenericWebhook41beab3a755721602a8faa6628d20a26Form

export const handleGenericWebhook = {
    '/api/webhooks/paystack/generic': handleGenericWebhook66b4e8c24e7be65b258563bd7f52c267,
    '/api/webhooks/paystack/test': handleGenericWebhook41beab3a755721602a8faa6628d20a26,
}

const PaystackWebhookController = { handlePaymentWebhook, handleTransferWebhook, handleGenericWebhook }

export default PaystackWebhookController