@include('errors.layout', [
    'code'    => '429',
    'title'   => __('Too Many Requests'),
    'message' => __('You have made too many requests. Please wait a moment before trying again.'),
])
