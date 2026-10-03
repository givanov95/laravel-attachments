<?php

declare(strict_types=1);

namespace Givanov95\LaravelAttachments\Tests\Support;

use Givanov95\LaravelAttachments\Concerns\HasFiles;
use Givanov95\LaravelAttachments\Concerns\HasImages;
use Illuminate\Database\Eloquent\Model;

/**
 * Parent model owned by a user; guarded by AttachmentOwnerPolicy.
 */
class AttachmentOwner extends Model
{
    use HasFiles, HasImages;

    protected $table = 'attachment_owners';

    protected $fillable = ['user_id'];
}
