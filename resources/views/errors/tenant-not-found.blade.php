@include('errors.layout', [
    'code'    => '404',
    'title'   => __('Organisation Not Found'),
    'message' => __('The organisation ":id" does not exist. Please check the URL or contact your administrator.', ['id' => $tenantId ?? '']),
])
