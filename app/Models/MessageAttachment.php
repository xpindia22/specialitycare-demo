<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = ['message_id', 'path', 'original_name', 'mime_type', 'size'];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /** Human-friendly size, e.g. "1.2 MB". */
    public function humanSize(): string
    {
        $b = (int) $this->size;
        if ($b < 1024) return $b . ' B';
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        return round($b / 1048576, 1) . ' MB';
    }

    /** FontAwesome icon for the file type. */
    public function icon(): string
    {
        return match (true) {
            $this->isPdf()                                              => 'fa-file-pdf',
            str_contains((string) $this->mime_type, 'word'),
            str_ends_with($this->original_name, '.doc'),
            str_ends_with($this->original_name, '.docx')               => 'fa-file-word',
            str_contains((string) $this->mime_type, 'sheet'),
            str_contains((string) $this->mime_type, 'excel')           => 'fa-file-excel',
            $this->isImage()                                            => 'fa-file-image',
            default                                                     => 'fa-file',
        };
    }
}
