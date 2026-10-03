<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Support;

use Givanov95\LaravelAttachments\Concerns\HasFiles;
use Givanov95\LaravelAttachments\Concerns\HasImages;
use Illuminate\Database\Eloquent\Model;

/**
 * Same table as AttachmentOwner, but no policy is registered for it.
 */
class PolicylessOwner extends Model
{
    use HasFiles, HasImages;

    protected $table = 'attachment_owners';

    protected $fillable = ['user_id'];
}
