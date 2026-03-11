<?php

namespace Modules\AccessControl\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Generates paths in the format:
 *   access-control/{collection_name}/{model_id}/
 *
 * Example for a user avatar:
 *   access-control/users/550e8400-e29b-41d4-a716-446655440000/
 */
class AccessControlPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return "access-control/{$media->collection_name}/{$media->model_id}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive-images/';
    }
}
