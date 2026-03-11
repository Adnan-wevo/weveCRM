@include('errors.layout', [
    'code'    => '403',
    'title'   => __('Access Denied'),
    'message' => $exception->getMessage() ?: __("You don't have permission to access this page."),
])
