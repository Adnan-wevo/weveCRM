@include('errors.layout', [
    'code'    => '503',
    'title'   => __('Service Unavailable'),
    'message' => $exception->getMessage() ?: __('We are down for maintenance. Please check back soon.'),
])
